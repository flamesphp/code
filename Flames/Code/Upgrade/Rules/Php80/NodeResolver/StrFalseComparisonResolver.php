<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeResolver;

use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class StrFalseComparisonResolver
{
    public function __construct(private ValueResolver $valueResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param string[] $oldStrFuncNames
     * @param \PhpParser\Node\Expr\BinaryOp\Identical|\PhpParser\Node\Expr\BinaryOp\NotIdentical|\PhpParser\Node\Expr\BinaryOp\Equal|\PhpParser\Node\Expr\BinaryOp\NotEqual $expr
     */
    public function resolve($expr, array $oldStrFuncNames): ?FuncCall
    {
        if ($this->valueResolver->isFalse($expr->left)) {
            if (!$expr->right instanceof FuncCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isNames($expr->right, $oldStrFuncNames)) {
                return null;
            }
            return $expr->right;
        }
        if ($this->valueResolver->isFalse($expr->right)) {
            if (!$expr->left instanceof FuncCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isNames($expr->left, $oldStrFuncNames)) {
                return null;
            }
            return $expr->left;
        }
        return null;
    }
}
