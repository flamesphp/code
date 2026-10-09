<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\For_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\Rules\EarlyReturn\NodeTransformer\ConditionInverter;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadIfForeachForRectorTest
 */
final class RemoveDeadIfForeachForRector extends AbstractRector
{
    private bool $hasChanged = \false;
    public function __construct(private readonly ConditionInverter $conditionInverter, private readonly BetterNodeFinder $betterNodeFinder, private readonly StmtsManipulator $stmtsManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove if, foreach and for that does not do anything', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($value, $differentValue)
    {
        if ($value) {
        }

        foreach ($values as $value) {
        }

        return $differentValue;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($value, $differentValue)
    {
        return $differentValue;
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
        return NodeGroup::STMTS_AWARE;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?\PhpParser\Node
    {
        if ($node->stmts === null) {
            return null;
        }
        $this->hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof If_ && !$stmt instanceof For_ && !$stmt instanceof Foreach_) {
                continue;
            }
            if ($stmt->stmts !== []) {
                continue;
            }
            if ($stmt instanceof If_) {
                $this->processIf($stmt, $key, $node);
                continue;
            }
            $this->processForForeach($stmt, $key, $node);
        }
        if ($this->hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param StmtsAware $stmtsAware
     */
    private function processIf(If_ $if, int $key, Node $stmtsAware): void
    {
        if ($if->elseifs !== []) {
            return;
        }
        // useless if ()
        if (!$if->else instanceof Else_) {
            if ($this->hasNodeSideEffect($if->cond)) {
                return;
            }
            unset($stmtsAware->stmts[$key]);
            $this->hasChanged = \true;
            return;
        }
        $if->cond = $this->conditionInverter->createInvertedCondition($if->cond);
        $if->stmts = $if->else->stmts;
        $if->else = null;
        $this->hasChanged = \true;
    }
    /**
     * @param StmtsAware $stmtsAware
     * @param \PhpParser\Node\Stmt\For_|\PhpParser\Node\Stmt\Foreach_ $for
     */
    private function processForForeach($for, int $key, Node $stmtsAware): void
    {
        if ($for instanceof For_) {
            $variables = $this->betterNodeFinder->findInstanceOf(array_merge($for->init, $for->cond, $for->loop), Variable::class);
            foreach ($variables as $variable) {
                if ($this->stmtsManipulator->isVariableUsedInNextStmt($stmtsAware, $key + 1, (string) $this->getName($variable))) {
                    return;
                }
            }
            unset($stmtsAware->stmts[$key]);
            $this->hasChanged = \true;
            return;
        }
        // the iterated expression itself may have side effects, e.g. foreach (generator() as $event) {}
        if ($this->hasNodeSideEffect($for->expr)) {
            return;
        }
        $exprs = [$for->expr, $for->valueVar, $for->valueVar];
        $variables = $this->betterNodeFinder->findInstanceOf($exprs, Variable::class);
        foreach ($variables as $variable) {
            if ($this->stmtsManipulator->isVariableUsedInNextStmt($stmtsAware, $key + 1, (string) $this->getName($variable))) {
                return;
            }
        }
        unset($stmtsAware->stmts[$key]);
        $this->hasChanged = \true;
    }
    private function hasNodeSideEffect(Expr $expr): bool
    {
        return $this->betterNodeFinder->hasInstancesOf($expr, [CallLike::class, Assign::class]);
    }
}
