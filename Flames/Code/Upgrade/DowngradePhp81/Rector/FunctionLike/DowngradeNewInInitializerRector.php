<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Error;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rules\Php72\NodeFactory\AnonymousFunctionFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/new_in_initializers
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\FunctionLike\DowngradeNewInInitializerRectorTest
 */
final class DowngradeNewInInitializerRector extends AbstractRector
{
    public function __construct(private readonly AnonymousFunctionFactory $anonymousFunctionFactory, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace New in initializers', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private Logger $logger = new NullLogger,
    ) {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private ?Logger $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
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
        return [FunctionLike::class];
    }
    /**
     * @param FunctionLike $node
     */
    public function refactor(Node $node): ?FunctionLike
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        /** @var ClassMethod|Closure|Function_ $node */
        $node = $this->convertArrowFunctionToClosure($node);
        return $this->replaceNewInParams($node);
    }
    private function shouldSkip(FunctionLike $functionLike): bool
    {
        foreach ($functionLike->getParams() as $param) {
            if ($this->isParamSkipped($param)) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    private function convertArrowFunctionToClosure(FunctionLike $functionLike): FunctionLike
    {
        if (!$functionLike instanceof ArrowFunction) {
            return $functionLike;
        }
        $stmts = [new Return_($functionLike->expr)];
        return $this->anonymousFunctionFactory->create($functionLike->params, $stmts, $functionLike->returnType, $functionLike->static);
    }
    private function isParamSkipped(Param $param): bool
    {
        if ($param->var instanceof Error) {
            return \true;
        }
        if (!$param->default instanceof Expr) {
            return \true;
        }
        $hasNew = (bool) $this->betterNodeFinder->findFirstInstanceOf($param->default, New_::class);
        if (!$hasNew) {
            return \true;
        }
        return $param->type instanceof IntersectionType;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_
     */
    private function replaceNewInParams($functionLike)
    {
        $isConstructor = $functionLike instanceof ClassMethod && $this->isName($functionLike, MethodName::CONSTRUCT);
        $stmts = [];
        foreach ($functionLike->getParams() as $param) {
            if ($this->isParamSkipped($param)) {
                continue;
            }
            /** @var Expr $default */
            $default = $param->default;
            /** @var Variable $paramVar */
            $paramVar = $param->var;
            // check for property promotion
            if ($isConstructor && $param->flags > 0) {
                $propertyFetch = new PropertyFetch(new Variable('this'), $paramVar->name);
                $coalesce = new Coalesce($param->var, $default);
                $assign = new Assign($propertyFetch, $coalesce);
                if ($param->type !== null) {
                    $param->type = $this->ensureNullableType($param->type);
                }
            } else {
                $assign = new AssignCoalesce($param->var, $default);
            }
            // recheck after
            if ($isConstructor && $param->type !== null) {
                $param->type = $this->ensureNullableType($param->type);
            }
            $stmts[] = new Expression($assign);
            $param->default = $this->nodeFactory->createNull();
        }
        if ($functionLike->stmts === null) {
            return $functionLike;
        }
        $functionLike->stmts ??= [];
        $functionLike->stmts = array_merge($stmts, $functionLike->stmts);
        return $functionLike;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType $type
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType
     */
    private function ensureNullableType($type)
    {
        if ($type instanceof NullableType) {
            return $type;
        }
        if (!$type instanceof ComplexType) {
            return new NullableType($type);
        }
        if ($type instanceof UnionType) {
            foreach ($type->types as $typeChild) {
                if (!$typeChild instanceof Identifier) {
                    continue;
                }
                if ($typeChild->toLowerString() === 'null') {
                    return $type;
                }
            }
            $type->types[] = new Identifier('null');
            return $type;
        }
        throw new ShouldNotHappenException();
    }
}
