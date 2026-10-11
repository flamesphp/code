<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Rules\DeadCode\SideEffect\SideEffectNodeDetector;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt\RemoveConditionExactReturnRectorTest
 */
final class RemoveConditionExactReturnRector extends AbstractRector
{
    public function __construct(private readonly SideEffectNodeDetector $sideEffectNodeDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove if with condition and return with same expr, followed by compared expr return', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(array $items)
    {
        if ($items === []) {
            return [];
        }

        return $items;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(array $items)
    {
        return $items;
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
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof If_) {
                continue;
            }
            $soleIfReturn = $this->matchSoleIfReturn($stmt);
            if (!$soleIfReturn instanceof Return_) {
                continue;
            }
            if (!$stmt->cond instanceof Identical && !$stmt->cond instanceof Equal) {
                continue;
            }
            $identicalOrEqual = $stmt->cond;
            if ($this->sideEffectNodeDetector->detect($identicalOrEqual->right)) {
                continue;
            }
            if (!$this->nodeComparator->areNodesEqual($identicalOrEqual->right, $soleIfReturn->expr)) {
                continue;
            }
            $comparedVariable = $identicalOrEqual->left;
            // next if must be return of the same var
            $nextStmt = $node->stmts[$key + 1] ?? null;
            if (!$nextStmt instanceof Return_) {
                continue;
            }
            if (!$nextStmt->expr instanceof Expr) {
                continue;
            }
            if (!$this->nodeComparator->areNodesEqual($nextStmt->expr, $comparedVariable)) {
                continue;
            }
            // remove next if
            unset($node->stmts[$key + 1]);
            // replace if with return
            $node->stmts[$key] = $nextStmt;
            return $node;
        }
        return null;
    }
    private function matchSoleIfReturn(If_ $if): ?Return_
    {
        if (count($if->stmts) !== 1) {
            return null;
        }
        $soleIfStmt = $if->stmts[0];
        if (!$soleIfStmt instanceof Return_) {
            return null;
        }
        return $soleIfStmt;
    }
}
