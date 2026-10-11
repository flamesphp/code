<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\Rules\DeadCode\SideEffect\SideEffectNodeDetector;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Stmt\RemoveNextSameValueConditionRectorTest
 */
final class RemoveNextSameValueConditionRector extends AbstractRector
{
    public function __construct(private readonly SideEffectNodeDetector $sideEffectNodeDetector, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove already checked if condition repeated in the very next stmt', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(array $items)
    {
        $count = 100;
        if ($items === []) {
            $count = 0;
        }

        if ($items === []) {
            return $count;
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function __construct(array $items)
    {
        $count = 100;
        if ($items === []) {
            $count = 0;
            return $count;
        }
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
            // only when no elseif/else in current if
            if ($stmt->elseifs !== []) {
                continue;
            }
            if ($stmt->else instanceof Else_) {
                continue;
            }
            $nextStmt = $node->stmts[$key + 1] ?? null;
            if (!$nextStmt instanceof If_) {
                continue;
            }
            if (!$this->nodeComparator->areNodesEqual($stmt->cond, $nextStmt->cond)) {
                continue;
            }
            // only when no elseif/else in next stmt
            if ($nextStmt->elseifs !== []) {
                continue;
            }
            if ($nextStmt->else instanceof Else_) {
                continue;
            }
            // first condition must be without side effect
            if ($this->sideEffectNodeDetector->detect($stmt->cond)) {
                continue;
            }
            if ($this->isCondVariableUsedInIfBody($stmt)) {
                continue;
            }
            $stmt->setAttribute(AttributeKey::COMMENTS, array_merge($stmt->getAttribute(AttributeKey::COMMENTS) ?? [], $nextStmt->getAttribute(AttributeKey::COMMENTS) ?? []));
            $stmt->stmts = array_merge($stmt->stmts, $nextStmt->stmts);
            // remove next node
            unset($node->stmts[$key + 1]);
            return $node;
        }
        return null;
    }
    private function isCondVariableUsedInIfBody(If_ $if): bool
    {
        $condVariables = $this->betterNodeFinder->findInstancesOf($if->cond, [Variable::class]);
        foreach ($condVariables as $condVariable) {
            $condVariableName = $this->getName($condVariable);
            if ($condVariableName === null) {
                continue;
            }
            if ($this->betterNodeFinder->findVariableOfName($if->stmts, $condVariableName) instanceof Variable) {
                return \true;
            }
        }
        return \false;
    }
}
