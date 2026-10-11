<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('invalidateExprNode')]
final class InvalidateExprNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $expr;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $expr
    )
    {
        $this->expr = $expr;
        parent::__construct($expr->getAttributes());
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_InvalidateExpr';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
