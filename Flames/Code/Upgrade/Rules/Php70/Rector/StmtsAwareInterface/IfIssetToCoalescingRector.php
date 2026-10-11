<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\StmtsAwareInterface;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Isset_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRectorTest
 */
final class IfIssetToCoalescingRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `if` with `isset` and `return` to coalesce', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private $items = [];

    public function resolve($key)
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        return 'fallback value';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    private $items = [];

    public function resolve($key)
    {
        return $this->items[$key] ?? 'fallback value';
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
            if (!$stmt instanceof Return_) {
                continue;
            }
            if (!$stmt->expr instanceof Expr) {
                continue;
            }
            $previousStmt = $node->stmts[$key - 1] ?? null;
            if (!$previousStmt instanceof If_) {
                continue;
            }
            if (!$previousStmt->cond instanceof Isset_) {
                continue;
            }
            $ifOnlyStmt = $this->matchBareIfOnlyStmt($previousStmt);
            if (!$ifOnlyStmt instanceof Return_) {
                continue;
            }
            if (!$ifOnlyStmt->expr instanceof Expr) {
                continue;
            }
            $ifIsset = $previousStmt->cond;
            if (!$this->nodeComparator->areNodesEqual($ifOnlyStmt->expr, $ifIsset->vars[0])) {
                continue;
            }
            if ($stmt->expr instanceof Assign && $this->nodeComparator->areNodesEqual($ifOnlyStmt->expr, $stmt->expr->var)) {
                continue;
            }
            unset($node->stmts[$key - 1]);
            if ($stmt->expr instanceof Ternary) {
                $stmt->expr->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \true);
            }
            $stmt->expr = new Coalesce($ifOnlyStmt->expr, $stmt->expr);
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NULL_COALESCE;
    }
    private function matchBareIfOnlyStmt(If_ $if): ?Stmt
    {
        if ($if->else instanceof Else_) {
            return null;
        }
        if ($if->elseifs !== []) {
            return null;
        }
        if (count($if->stmts) !== 1) {
            return null;
        }
        return $if->stmts[0];
    }
}
