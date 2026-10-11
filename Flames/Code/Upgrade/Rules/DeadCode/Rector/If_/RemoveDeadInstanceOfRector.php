<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\If_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeManipulator\IfManipulator;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\If_\RemoveDeadInstanceOfRectorTest
 */
final class RemoveDeadInstanceOfRector extends AbstractRector
{
    public function __construct(private readonly IfManipulator $ifManipulator, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove dead instanceof check on type hinted variable', [new CodeSample(<<<'CODE_SAMPLE'
function run(stdClass $stdClass)
{
    if (! $stdClass instanceof stdClass) {
        return false;
    }

    return true;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function run(stdClass $stdClass)
{
    return true;
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
     * @return Stmt[]|null|NodeVisitor::REMOVE_NODE|If_
     */
    public function refactor(Node $node)
    {
        if (!$this->ifManipulator->isIfWithoutElseAndElseIfs($node)) {
            return null;
        }
        if ($node->cond instanceof BooleanNot && $node->cond->expr instanceof Instanceof_) {
            return $this->refactorStmtAndInstanceof($node, $node->cond->expr);
        }
        if ($node->cond instanceof BooleanAnd) {
            return $this->refactorIfWithBooleanAnd($node);
        }
        if ($node->cond instanceof Instanceof_) {
            return $this->refactorStmtAndInstanceof($node, $node->cond);
        }
        return null;
    }
    /**
     * @return null|Stmt[]|NodeVisitor::REMOVE_NODE
     */
    private function refactorStmtAndInstanceof(If_ $if, Instanceof_ $instanceof)
    {
        if ($this->isInstanceofTheSameType($instanceof) !== \true) {
            return null;
        }
        if ($this->shouldSkipFromNotTypedParam($instanceof)) {
            return null;
        }
        if ($instanceof->expr instanceof Assign) {
            $instanceof->expr->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \false);
            $assignExpression = new Expression($instanceof->expr);
            return array_merge([$assignExpression], $if->stmts);
        }
        if ($if->cond !== $instanceof) {
            return NodeVisitor::REMOVE_NODE;
        }
        if ($if->stmts === []) {
            return NodeVisitor::REMOVE_NODE;
        }
        // unwrap stmts
        return $if->stmts;
    }
    private function shouldSkipFromNotTypedParam(Instanceof_ $instanceof): bool
    {
        $nativeParamType = $this->nodeTypeResolver->getNativeType($instanceof->expr);
        return $nativeParamType instanceof MixedType;
    }
    private function isPropertyFetch(Expr $expr): bool
    {
        if ($expr instanceof PropertyFetch) {
            return \true;
        }
        return $expr instanceof StaticPropertyFetch;
    }
    private function isInstanceofTheSameType(Instanceof_ $instanceof): ?bool
    {
        if (!$instanceof->class instanceof Name) {
            return null;
        }
        // handled in another rule
        if ($this->isPropertyFetch($instanceof->expr) || $instanceof->expr instanceof CallLike) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($instanceof);
        if ($classReflection instanceof ClassReflection && $classReflection->isTrait()) {
            return null;
        }
        $exprType = $this->nodeTypeResolver->getNativeType($instanceof->expr);
        if (!$exprType instanceof ObjectType) {
            return null;
        }
        $className = $instanceof->class->toString();
        return $exprType->isInstanceOf($className)->yes();
    }
    private function refactorIfWithBooleanAnd(If_ $if): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_
    {
        if (!$if->cond instanceof BooleanAnd) {
            return null;
        }
        $booleanAnd = $if->cond;
        if (!$booleanAnd->left instanceof Instanceof_) {
            return null;
        }
        $instanceof = $booleanAnd->left;
        if ($this->isInstanceofTheSameType($instanceof) !== \true) {
            return null;
        }
        $if->cond = $booleanAnd->right;
        return $if;
    }
}
