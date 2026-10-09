<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitor;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\IntersectionType;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\SafeLeftTypeBooleanAndOrAnalyzer;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRectorTest
 */
final class RemoveAlwaysTrueIfConditionRector extends AbstractRector
{
    /**
     * Functions that depend on runtime state (autoloading, eval(), loaded extensions).
     * PHPStan may narrow their result to a constant true, but it can change at runtime,
     * so the condition must not be treated as always true.
     *
     * @var string[]
     */
    private const array RUNTIME_STATE_FUNCTIONS = ['class_exists', 'interface_exists', 'trait_exists', 'enum_exists', 'function_exists', 'method_exists', 'property_exists', 'defined', 'extension_loaded'];
    public function __construct(private readonly ExprAnalyzer $exprAnalyzer, private readonly BetterNodeFinder $betterNodeFinder, private readonly SafeLeftTypeBooleanAndOrAnalyzer $safeLeftTypeBooleanAndOrAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove if condition that is always true', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function go()
    {
        if (1 === 1) {
            return 'yes';
        }

        return 'no';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function go()
    {
        return 'yes';

        return 'no';
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
        return [If_::class];
    }
    /**
     * @param If_ $node
     * @return int|null|Stmt[]|If_
     */
    public function refactor(Node $node)
    {
        if ($node->cond instanceof BooleanAnd) {
            return $this->refactorIfWithBooleanAnd($node);
        }
        if ($node->else instanceof Else_) {
            return null;
        }
        // just one if
        if ($node->elseifs !== []) {
            return null;
        }
        $conditionStaticType = $this->nodeTypeResolver->getNativeType($node->cond);
        if (!$conditionStaticType->isTrue()->yes()) {
            return null;
        }
        if ($this->shouldSkipExpr($node->cond)) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if ($this->shouldSkipFromVariable($node->cond, $scope)) {
            return null;
        }
        $hasAssign = (bool) $this->betterNodeFinder->findFirstInstanceOf($node->cond, Assign::class);
        if ($hasAssign) {
            return null;
        }
        $type = $scope->getNativeType($node->cond);
        if (!$type->isTrue()->yes()) {
            return null;
        }
        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection && $classReflection->isTrait()) {
            return null;
        }
        if ($node->stmts === []) {
            return NodeVisitor::REMOVE_NODE;
        }
        // keep original comments
        if ($node->getComments() !== []) {
            $node->stmts[0]->setAttribute(AttributeKey::COMMENTS, array_merge($node->getComments(), $node->stmts[0]->getComments()));
        }
        return $node->stmts;
    }
    private function shouldSkipFromVariable(Expr $expr, Scope $scope): bool
    {
        /** @var Variable[] $variables */
        $variables = $this->betterNodeFinder->findInstancesOf($expr, [Variable::class]);
        foreach ($variables as $variable) {
            // maybe undefined variable is treated as null on some code paths, so the condition is not always true
            $variableName = $this->getName($variable);
            if (is_string($variableName) && !$scope->hasVariableType($variableName)->yes()) {
                return \true;
            }
            if ($this->exprAnalyzer->isNonTypedFromParam($variable)) {
                return \true;
            }
            $type = $this->nodeTypeResolver->getNativeType($variable);
            if ($type instanceof IntersectionType) {
                foreach ($type->getTypes() as $subType) {
                    if ($subType->isArray()->yes()) {
                        return \true;
                    }
                }
            }
        }
        return \false;
    }
    private function shouldSkipExpr(Expr $expr): bool
    {
        $hasNonStaticNode = (bool) $this->betterNodeFinder->findInstancesOf($expr, [PropertyFetch::class, StaticPropertyFetch::class, ArrayDimFetch::class, MethodCall::class, StaticCall::class]);
        if ($hasNonStaticNode) {
            return \true;
        }
        /** @var FuncCall[] $funcCalls */
        $funcCalls = $this->betterNodeFinder->findInstancesOf($expr, [FuncCall::class]);
        foreach ($funcCalls as $funcCall) {
            if ($this->isNames($funcCall, self::RUNTIME_STATE_FUNCTIONS)) {
                return \true;
            }
        }
        return \false;
    }
    private function refactorIfWithBooleanAnd(If_ $if): ?If_
    {
        if (!$if->cond instanceof BooleanAnd) {
            return null;
        }
        $booleanAnd = $if->cond;
        $leftType = $this->getType($booleanAnd->left);
        if (!$leftType->isTrue()->yes()) {
            return null;
        }
        if (!$this->safeLeftTypeBooleanAndOrAnalyzer->isSafe($booleanAnd)) {
            return null;
        }
        $if->cond = $booleanAnd->right;
        return $if;
    }
}
