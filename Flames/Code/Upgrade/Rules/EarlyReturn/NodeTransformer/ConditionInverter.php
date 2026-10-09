<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\EarlyReturn\NodeTransformer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\NodeManipulator\BinaryOpManipulator;
final readonly class ConditionInverter
{
    public function __construct(private BinaryOpManipulator $binaryOpManipulator)
    {
    }
    public function createInvertedCondition(Expr $expr): Expr
    {
        // inverse condition
        if ($expr instanceof BinaryOp) {
            $binaryOp = $this->binaryOpManipulator->invertCondition($expr);
            if (!$binaryOp instanceof BinaryOp) {
                return new BooleanNot($expr);
            }
            if ($binaryOp instanceof BooleanAnd) {
                return new BooleanNot($expr);
            }
            return $binaryOp;
        }
        if ($expr instanceof BooleanNot) {
            return $expr->expr;
        }
        return new BooleanNot($expr);
    }
}
