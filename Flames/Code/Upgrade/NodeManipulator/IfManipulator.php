<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class IfManipulator
{
    public function __construct(private \Flames\Code\Upgrade\NodeManipulator\StmtsManipulator $stmtsManipulator, private ValueResolver $valueResolver, private NodeComparator $nodeComparator)
    {
    }
    /**
     * Matches:
     *
     * if (<$value> !== null) {
     *     return $value;
     * }
     */
    public function matchIfNotNullReturnValue(If_ $if): ?Expr
    {
        if (count($if->stmts) !== 1) {
            return null;
        }
        $insideIfNode = $if->stmts[0];
        if (!$insideIfNode instanceof Return_) {
            return null;
        }
        if (!$if->cond instanceof NotIdentical) {
            return null;
        }
        return $this->matchComparedAndReturnedNode($if->cond, $insideIfNode);
    }
    public function isIfAndElseWithSameVariableAssignAsLastStmts(If_ $if, Expr $desiredExpr): bool
    {
        if (!$if->else instanceof Else_) {
            return \false;
        }
        if ((bool) $if->elseifs) {
            return \false;
        }
        $lastIfNode = $this->stmtsManipulator->getUnwrappedLastStmt($if->stmts);
        if (!$lastIfNode instanceof Assign) {
            return \false;
        }
        $lastElseNode = $this->stmtsManipulator->getUnwrappedLastStmt($if->else->stmts);
        if (!$lastElseNode instanceof Assign) {
            return \false;
        }
        if (!$lastIfNode->var instanceof Variable) {
            return \false;
        }
        if (!$this->nodeComparator->areNodesEqual($lastIfNode->var, $lastElseNode->var)) {
            return \false;
        }
        return $this->nodeComparator->areNodesEqual($desiredExpr, $lastElseNode->var);
    }
    /**
     * @param class-string<Stmt> $stmtClass
     */
    public function isIfWithOnly(If_ $if, string $stmtClass): bool
    {
        if (!$this->isIfWithoutElseAndElseIfs($if)) {
            return \false;
        }
        return $this->hasOnlyStmtOfType($if, $stmtClass);
    }
    public function isIfWithoutElseAndElseIfs(If_ $if): bool
    {
        if ($if->else instanceof Else_) {
            return \false;
        }
        return $if->elseifs === [];
    }
    private function matchComparedAndReturnedNode(NotIdentical $notIdentical, Return_ $return): ?Expr
    {
        if ($this->nodeComparator->areNodesEqual($notIdentical->left, $return->expr) && $this->valueResolver->isNull($notIdentical->right)) {
            return $notIdentical->left;
        }
        if (!$this->nodeComparator->areNodesEqual($notIdentical->right, $return->expr)) {
            return null;
        }
        if ($this->valueResolver->isNull($notIdentical->left)) {
            return $notIdentical->right;
        }
        return null;
    }
    /**
     * @param class-string<Stmt> $stmtClass
     */
    private function hasOnlyStmtOfType(If_ $if, string $stmtClass): bool
    {
        $stmts = $if->stmts;
        if (count($stmts) !== 1) {
            return \false;
        }
        return $stmts[0] instanceof $stmtClass;
    }
}
