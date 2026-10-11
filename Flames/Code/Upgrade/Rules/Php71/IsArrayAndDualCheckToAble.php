<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php71;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\NodeManipulator\BinaryOpManipulator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php71\ValueObject\TwoNodeMatch;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class IsArrayAndDualCheckToAble
{
    public function __construct(private BinaryOpManipulator $binaryOpManipulator, private NodeNameResolver $nodeNameResolver, private NodeComparator $nodeComparator)
    {
    }
    public function processBooleanOr(BooleanOr $booleanOr, string $type, string $newMethodName): ?FuncCall
    {
        $twoNodeMatch = $this->binaryOpManipulator->matchFirstAndSecondConditionNode($booleanOr, Instanceof_::class, FuncCall::class);
        if (!$twoNodeMatch instanceof TwoNodeMatch) {
            return null;
        }
        /** @var Instanceof_ $instanceofExpr */
        $instanceofExpr = $twoNodeMatch->getFirstExpr();
        /** @var FuncCall $funcCallExpr */
        $funcCallExpr = $twoNodeMatch->getSecondExpr();
        $instanceOfClass = $instanceofExpr->class;
        if ($instanceOfClass instanceof Expr) {
            return null;
        }
        if ((string) $instanceOfClass !== $type) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($funcCallExpr, 'is_array')) {
            return null;
        }
        if ($funcCallExpr->isFirstClassCallable()) {
            return null;
        }
        if (!isset($funcCallExpr->getArgs()[0])) {
            return null;
        }
        $firstArg = $funcCallExpr->getArgs()[0];
        $firstExprNode = $firstArg->value;
        if (!$this->nodeComparator->areNodesEqual($instanceofExpr->expr, $firstExprNode)) {
            return null;
        }
        // both use same Expr
        return new FuncCall(new Name($newMethodName), [new Arg($firstExprNode)]);
    }
}
