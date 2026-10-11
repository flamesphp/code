<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('setOffsetValueTypeExpr')]
final class SetOffsetValueTypeExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $var;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Expr $dim;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $value;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $var,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Expr $dim,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $value
    )
    {
        $this->var = $var;
        $this->dim = $dim;
        $this->value = $value;
        parent::__construct([]);
    }
    public function getVar(): Expr
    {
        return $this->var;
    }
    public function getDim(): ?Expr
    {
        return $this->dim;
    }
    public function getValue(): Expr
    {
        return $this->value;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_SetOffsetValueTypeExpr';
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
