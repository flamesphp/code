<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Coalesce as AssignOpCoalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Empty_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRectorTest
 */
final class StrictArrayParamDimFetchRector extends AbstractRector
{
    public function __construct(private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly TypeComparator $typeComparator, private readonly TypeFactory $typeFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add array type based on array dim fetch use', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function resolve($item)
    {
        return $item['name'];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function resolve(array $item)
    {
        return $item['name'];
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class];
    }
    /**
     * @param ClassMethod|Function_|Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        if ($node instanceof ClassMethod && $this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($node)) {
            return null;
        }
        if ($node instanceof ClassMethod && $this->parentClassMethodTypeOverrideGuard->isTypeGuardedClass($node)) {
            return null;
        }
        foreach ($node->getParams() as $param) {
            if ($param->type instanceof Node) {
                continue;
            }
            if ($param->variadic) {
                continue;
            }
            if ($param->default instanceof Expr && !$this->getType($param->default)->isArray()->yes()) {
                continue;
            }
            if (!$this->isParamAccessedArrayDimFetch($param, $node)) {
                continue;
            }
            $param->type = new Identifier('array');
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    private function isParamAccessedArrayDimFetch(Param $param, $functionLike): bool
    {
        if ($functionLike->stmts === null) {
            return \false;
        }
        $paramName = $this->getName($param);
        $isParamAccessedArrayDimFetch = \false;
        $this->traverseNodesWithCallable($functionLike->stmts, function (Node $node) use ($paramName, &$isParamAccessedArrayDimFetch): ?int {
            if ($node instanceof Class_ || $node instanceof FunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($this->shouldStop($node, $paramName)) {
                // force set to false to avoid too early replaced
                $isParamAccessedArrayDimFetch = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if (!$node instanceof ArrayDimFetch) {
                return null;
            }
            if (!$node->dim instanceof Expr) {
                return null;
            }
            if (!$node->var instanceof Variable) {
                return null;
            }
            if (!$this->isName($node->var, $paramName)) {
                return null;
            }
            // skip possible strings
            $variableType = $this->getType($node->var);
            if ($variableType->isString()->yes()) {
                // force set to false to avoid too early replaced
                $isParamAccessedArrayDimFetch = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            // skip integer in possibly string type as string can be accessed via int
            $dimType = $this->getType($node->dim);
            if ($dimType->isInteger()->yes() && $variableType->isString()->maybe()) {
                return null;
            }
            $variableType = $this->typeFactory->createMixedPassedOrUnionType([$variableType]);
            if ($variableType instanceof UnionType) {
                $isParamAccessedArrayDimFetch = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($this->isArrayAccess($variableType)) {
                $isParamAccessedArrayDimFetch = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            $isParamAccessedArrayDimFetch = \true;
            return null;
        });
        return $isParamAccessedArrayDimFetch;
    }
    private function isEchoed(Node $node, string $paramName): bool
    {
        if (!$node instanceof Echo_) {
            return \false;
        }
        $found = array_any($node->exprs, fn($expr) => $expr instanceof Variable && $this->isName($expr, $paramName));
        return $found;
    }
    private function shouldStop(Node $node, string $paramName): bool
    {
        $nodeToCheck = null;
        if ($node instanceof FuncCall && !$node->isFirstClassCallable() && $this->isNames($node, ['is_array', 'is_string', 'is_int', 'is_bool', 'is_float'])) {
            $firstArg = $node->getArgs()[0];
            $nodeToCheck = $firstArg->value;
        }
        if ($node instanceof Expression) {
            $nodeToCheck = $node->expr;
        }
        if ($node instanceof Coalesce) {
            $nodeToCheck = $node->left;
        }
        if ($node instanceof AssignOpCoalesce) {
            $nodeToCheck = $node->var;
        }
        if ($this->isMethodCall($paramName, $nodeToCheck)) {
            return \true;
        }
        if ($nodeToCheck instanceof Variable && $this->isName($nodeToCheck, $paramName)) {
            return \true;
        }
        if ($this->isEmptyOrEchoedOrCasted($node, $paramName)) {
            return \true;
        }
        if ($this->isPropertyFetchedOnArrayDimFetch($node, $paramName)) {
            return \true;
        }
        if ($this->isInstanceofParam($node, $paramName)) {
            return \true;
        }
        return $this->isReassignAndUseAsArg($node, $paramName);
    }
    private function isReassignAndUseAsArg(Node $node, string $paramName): bool
    {
        if (!$node instanceof Assign) {
            return \false;
        }
        if (!$node->var instanceof Variable) {
            return \false;
        }
        if (!$this->isName($node->var, $paramName)) {
            return \false;
        }
        if (!$node->expr instanceof CallLike) {
            return \false;
        }
        if ($node->expr->isFirstClassCallable()) {
            return \false;
        }
        $found = array_any($node->expr->getArgs(), fn($arg) => $arg->value instanceof Variable && $this->isName($arg->value, $paramName));
        return $found;
    }
    private function isEmptyOrEchoedOrCasted(Node $node, string $paramName): bool
    {
        if ($node instanceof Empty_ && $node->expr instanceof Variable && $this->isName($node->expr, $paramName)) {
            return \true;
        }
        if ($this->isEchoed($node, $paramName)) {
            return \true;
        }
        return $node instanceof Array_ && $node->expr instanceof Variable && $this->isName($node->expr, $paramName);
    }
    private function isPropertyFetchedOnArrayDimFetch(Node $node, string $paramName): bool
    {
        if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch) {
            return \false;
        }
        $fetchedOn = $node instanceof PropertyFetch ? $node->var : $node->class;
        if (!$fetchedOn instanceof ArrayDimFetch) {
            return \false;
        }
        return $fetchedOn->var instanceof Variable && $this->isName($fetchedOn->var, $paramName);
    }
    private function isInstanceofParam(Node $node, string $paramName): bool
    {
        return $node instanceof Instanceof_ && $node->expr instanceof Variable && $this->isName($node->expr, $paramName);
    }
    private function isMethodCall(string $paramName, ?Node $node): bool
    {
        if ($node instanceof MethodCall) {
            return $node->var instanceof Variable && $this->isName($node->var, $paramName);
        }
        return \false;
    }
    private function isArrayAccess(Type $type): bool
    {
        if (!$type instanceof ObjectType) {
            return \false;
        }
        return $this->typeComparator->isSubtype($type, new ObjectType('ArrayAccess'));
    }
}
