<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\FuncCallAndExpr;
final readonly class BinaryOpAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function matchFuncCallAndOtherExpr(BinaryOp $binaryOp, string $funcCallName): ?FuncCallAndExpr
    {
        if ($binaryOp->left instanceof FuncCall) {
            if (!$this->nodeNameResolver->isName($binaryOp->left, $funcCallName)) {
                return null;
            }
            return new FuncCallAndExpr($binaryOp->left, $binaryOp->right);
        }
        if ($binaryOp->right instanceof FuncCall) {
            if (!$this->nodeNameResolver->isName($binaryOp->right, $funcCallName)) {
                return null;
            }
            return new FuncCallAndExpr($binaryOp->right, $binaryOp->left);
        }
        return null;
    }
}
