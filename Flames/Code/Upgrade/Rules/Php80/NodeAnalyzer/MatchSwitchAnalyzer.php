<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php80\Enum\MatchKind;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\CondAndExpr;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
final readonly class MatchSwitchAnalyzer
{
    public function __construct(private \Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\SwitchAnalyzer $switchAnalyzer, private NodeNameResolver $nodeNameResolver, private NodeComparator $nodeComparator, private BetterStandardPrinter $betterStandardPrinter)
    {
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    public function isReturnCondsAndExprs(array $condAndExprs): bool
    {
        $found = array_any($condAndExprs, fn($condAndExpr) => $condAndExpr->equalsMatchKind(MatchKind::RETURN));
        return $found;
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    public function shouldSkipSwitch(Switch_ $switch, array $condAndExprs, ?Stmt $nextStmt): bool
    {
        if ($condAndExprs === []) {
            return \true;
        }
        if (!$this->switchAnalyzer->hasEachCaseBreak($switch)) {
            return \true;
        }
        if ($this->switchAnalyzer->hasDifferentTypeCases($switch->cases, $switch->cond)) {
            return \true;
        }
        if (!$this->switchAnalyzer->hasEachCaseSingleStmt($switch)) {
            return \false;
        }
        if ($this->switchAnalyzer->hasDefaultSingleStmt($switch)) {
            return \false;
        }
        // is followed by return? is considered implicit default
        if ($this->isNextStmtReturnWithExpr($switch, $nextStmt)) {
            return \false;
        }
        return !$nextStmt instanceof Expression || !$nextStmt->expr instanceof Throw_;
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    public function haveCondAndExprsMatchPotential(array $condAndExprs): bool
    {
        $uniqueCondAndExprKinds = $this->resolveUniqueKindsWithoutThrows($condAndExprs);
        if (count($uniqueCondAndExprKinds) > 1) {
            return \false;
        }
        $assignVariableNames = [];
        foreach ($condAndExprs as $condAndExpr) {
            $expr = $condAndExpr->getExpr();
            if (!$expr instanceof Assign) {
                continue;
            }
            if ($expr->var instanceof ArrayDimFetch) {
                $assignVariableNames[] = $this->betterStandardPrinter->print($expr->var);
            } else {
                $assignVariableNames[] = $expr->var::class . $this->nodeNameResolver->getName($expr->var);
            }
        }
        $assignVariableNames = array_unique($assignVariableNames);
        return count($assignVariableNames) <= 1;
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    public function hasCondsAndExprDefaultValue(array $condAndExprs): bool
    {
        $found = array_any($condAndExprs, fn($condAndExpr) => $condAndExpr->getCondExprs() === null);
        return $found;
    }
    public function hasDefaultValue(Match_ $match): bool
    {
        foreach ($match->arms as $matchArm) {
            if ($matchArm->conds === null) {
                return \true;
            }
            if ($matchArm->conds === []) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     * @return array<MatchKind::*>
     */
    private function resolveUniqueKindsWithoutThrows(array $condAndExprs): array
    {
        $condAndExprKinds = [];
        foreach ($condAndExprs as $condAndExpr) {
            if ($condAndExpr->equalsMatchKind(MatchKind::THROW)) {
                continue;
            }
            $condAndExprKinds[] = $condAndExpr->getMatchKind();
        }
        return array_unique($condAndExprKinds);
    }
    private function isNextStmtReturnWithExpr(Switch_ $switch, ?Stmt $nextStmt): bool
    {
        if (!$nextStmt instanceof Return_) {
            return \false;
        }
        if (!$nextStmt->expr instanceof Expr) {
            return \false;
        }
        foreach ($switch->cases as $case) {
            /** @var Expression[] $expressions */
            $expressions = array_filter($case->stmts, static fn(Node $node): bool => $node instanceof Expression);
            foreach ($expressions as $expression) {
                if (!$expression->expr instanceof Assign) {
                    continue;
                }
                if (!$this->nodeComparator->areNodesEqual($expression->expr->var, $nextStmt->expr)) {
                    return \false;
                }
            }
        }
        return \true;
    }
}
