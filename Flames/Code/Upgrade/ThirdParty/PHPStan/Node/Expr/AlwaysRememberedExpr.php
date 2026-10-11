<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/** Wraps an expression so its type is always remembered in the scope, bypassing impurity checks. */
#[ReferencedByTurboExtension('alwaysRememberedExpr')]
final class AlwaysRememberedExpr extends Expr implements VirtualNode
{
    public Expr $expr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $nativeType;
    public function __construct(
        Expr $expr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $nativeType
    )
    {
        $this->expr = $expr;
        $this->type = $type;
        $this->nativeType = $nativeType;
        parent::__construct([]);
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    public function getExprType(): Type
    {
        return $this->type;
    }
    public function getNativeExprType(): Type
    {
        return $this->nativeType;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_AlwaysRememberedExpr';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return ['expr'];
    }
}
