<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeFactory;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Rules\Php80\Enum\MatchKind;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\MatchSwitchAnalyzer;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\CondAndExpr;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\MatchResult;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class MatchFactory
{
    public function __construct(private \Flames\Code\Upgrade\Rules\Php80\NodeFactory\MatchArmsFactory $matchArmsFactory, private MatchSwitchAnalyzer $matchSwitchAnalyzer, private NodeComparator $nodeComparator)
    {
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    public function createFromCondAndExprs(Expr $condExpr, array $condAndExprs, ?Stmt $nextStmt): ?MatchResult
    {
        $shouldRemoteNextStmt = \false;
        // is default value missing? maybe it can be found in next stmt
        if (!$this->matchSwitchAnalyzer->hasCondsAndExprDefaultValue($condAndExprs)) {
            // 1. is followed by throws stmts?
            if ($nextStmt instanceof Expression && $nextStmt->expr instanceof Throw_) {
                $throw = $nextStmt->expr;
                $condAndExprs[] = new CondAndExpr([], $throw, MatchKind::RETURN);
                $shouldRemoteNextStmt = \true;
            }
            // 2. is followed by return expr
            // implicit return default after switch
            if ($nextStmt instanceof Return_ && $nextStmt->expr instanceof Expr) {
                // @todo this should be improved
                $expr = $this->resolveAssignVar($condAndExprs);
                if ($expr instanceof ArrayDimFetch) {
                    return null;
                }
                if ($expr instanceof Expr && !$this->nodeComparator->areNodesEqual($nextStmt->expr, $expr)) {
                    return null;
                }
                $shouldRemoteNextStmt = !$expr instanceof Expr;
                $condAndExprs[] = new CondAndExpr([], $nextStmt->expr, MatchKind::RETURN, $nextStmt->getComments());
            }
        }
        $matchArms = $this->matchArmsFactory->createFromCondAndExprs($condAndExprs);
        $match = new Match_($condExpr, $matchArms);
        return new MatchResult($match, $shouldRemoteNextStmt);
    }
    /**
     * @param CondAndExpr[] $condAndExprs
     */
    private function resolveAssignVar(array $condAndExprs): ?Expr
    {
        foreach ($condAndExprs as $condAndExpr) {
            $expr = $condAndExpr->getExpr();
            if (!$expr instanceof Assign) {
                continue;
            }
            return $expr->var;
        }
        return null;
    }
}
