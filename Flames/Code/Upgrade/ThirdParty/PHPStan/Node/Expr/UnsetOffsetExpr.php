<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('unsetOffsetExpr')]
final class UnsetOffsetExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $var;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $dim;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $var,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $dim
    )
    {
        $this->var = $var;
        $this->dim = $dim;
        parent::__construct([]);
    }
    public function getVar(): Expr
    {
        return $this->var;
    }
    public function getDim(): Expr
    {
        return $this->dim;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_UnsetOffsetExpr';
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
