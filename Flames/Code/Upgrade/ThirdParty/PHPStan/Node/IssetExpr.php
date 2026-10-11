<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('issetExpr')]
final class IssetExpr extends Expr implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $expr;
    /**
     * @api
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $expr
    )
    {
        $this->expr = $expr;
        parent::__construct([]);
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_IssetExpr';
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
