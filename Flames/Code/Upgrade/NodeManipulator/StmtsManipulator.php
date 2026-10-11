<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Finally_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TryCatch;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\ExprUsedInNodeAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class StmtsManipulator
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private BetterNodeFinder $betterNodeFinder, private NodeComparator $nodeComparator, private ExprUsedInNodeAnalyzer $exprUsedInNodeAnalyzer)
    {
    }
    /**
     * @param Stmt[] $stmts
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt
     */
    public function getUnwrappedLastStmt(array $stmts)
    {
        if ($stmts === []) {
            return null;
        }
        $lastStmtKey = array_key_last($stmts);
        $lastStmt = $stmts[$lastStmtKey];
        if ($lastStmt instanceof Expression) {
            $lastStmt->expr->setAttribute(AttributeKey::COMMENTS, $lastStmt->getAttribute(AttributeKey::COMMENTS));
            return $lastStmt->expr;
        }
        return $lastStmt;
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function filterOutExistingStmts(ClassMethod $classMethod, array $stmts): array
    {
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable((array) $classMethod->stmts, function (Node $node) use (&$stmts) {
            foreach ($stmts as $key => $assign) {
                if (!$this->nodeComparator->areNodesEqual($node, $assign)) {
                    continue;
                }
                unset($stmts[$key]);
            }
            return null;
        });
        return $stmts;
    }
    /**
     * @param StmtsAware $stmtsAware
     */
    public function isVariableUsedInNextStmt(Node $stmtsAware, int $jumpToKey, string $variableName): bool
    {
        if ($stmtsAware->stmts === null) {
            return \false;
        }
        $lastKey = array_key_last($stmtsAware->stmts);
        $stmts = [];
        for ($key = $jumpToKey; $key <= $lastKey; ++$key) {
            if (!isset($stmtsAware->stmts[$key])) {
                // can be just removed
                continue;
            }
            $stmts[] = $stmtsAware->stmts[$key];
        }
        if ($stmtsAware instanceof TryCatch) {
            $stmts = array_merge($stmts, $stmtsAware->catches);
            if ($stmtsAware->finally instanceof Finally_) {
                $stmts = array_merge($stmts, $stmtsAware->finally->stmts);
            }
        }
        $variable = new Variable($variableName);
        return (bool) $this->betterNodeFinder->findFirst($stmts, fn(Node $subNode): bool => $this->exprUsedInNodeAnalyzer->isUsed($subNode, $variable));
    }
}
