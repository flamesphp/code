<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\ComparedExprAndValueExpr;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
final readonly class InArrayFromRepeatedCompareFactory
{
    public function __construct(private NodeComparator $nodeComparator, private NodeFactory $nodeFactory, private ExprAnalyzer $exprAnalyzer)
    {
    }
    /**
     * Builds the "$value, [...]" args of an in_array() call from a repeated compare chain,
     * once all compared expressions are confirmed equal. Returns null when the chain is too
     * short, the compared expressions differ, or a value expression is not safe to evaluate eagerly.
     *
     * @param ComparedExprAndValueExpr[] $comparedExprAndValueExprs
     * @return Arg[]|null
     */
    public function createInArrayArgs(array $comparedExprAndValueExprs): ?array
    {
        if (count($comparedExprAndValueExprs) < 3) {
            return null;
        }
        $valueExprs = [];
        foreach ($comparedExprAndValueExprs as $comparedExprAndValueExpr) {
            $valueExpr = $comparedExprAndValueExpr->getValueExpr();
            // the array literal evaluates every item up front, while the &&/|| chain stops early,
            // e.g. null !== $a && null !== $a->get() would call get() on null
            if (!$this->isSafeToEvaluateEagerly($valueExpr)) {
                return null;
            }
            $valueExprs[] = $valueExpr;
        }
        /** @var ComparedExprAndValueExpr $firstComparedExprAndValue */
        $firstComparedExprAndValue = array_pop($comparedExprAndValueExprs);
        // all compared expr must be equal
        foreach ($comparedExprAndValueExprs as $comparedExprAndValueExpr) {
            if (!$this->nodeComparator->areNodesEqual($firstComparedExprAndValue->getComparedExpr(), $comparedExprAndValueExpr->getComparedExpr())) {
                return null;
            }
        }
        $array = $this->nodeFactory->createArray($valueExprs);
        return $this->nodeFactory->createArgs([$firstComparedExprAndValue->getComparedExpr(), $array]);
    }
    private function isSafeToEvaluateEagerly(Expr $expr): bool
    {
        // reading a plain variable has no side effect; $$name could evaluate a call
        if ($expr instanceof Variable) {
            return is_string($expr->name);
        }
        return !$this->exprAnalyzer->isDynamicExpr($expr);
    }
}
