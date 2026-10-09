<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class ForeachAnalyzer
{
    public function __construct(private NodeComparator $nodeComparator)
    {
    }
    /**
     * Matches$
     * foreach ($values as $value) {
     *      <$assigns[]> = $value;
     * }
     */
    public function matchAssignItemsOnlyForeachArrayVariable(Foreach_ $foreach): ?Expr
    {
        if (count($foreach->stmts) !== 1) {
            return null;
        }
        $onlyStatement = $foreach->stmts[0];
        if ($onlyStatement instanceof Expression) {
            $onlyStatement = $onlyStatement->expr;
        }
        if (!$onlyStatement instanceof Assign) {
            return null;
        }
        if (!$onlyStatement->var instanceof ArrayDimFetch) {
            return null;
        }
        if ($onlyStatement->var->dim instanceof Expr) {
            return null;
        }
        if (!$this->nodeComparator->areNodesEqual($foreach->valueVar, $onlyStatement->expr)) {
            return null;
        }
        return $onlyStatement->var->var;
    }
}
