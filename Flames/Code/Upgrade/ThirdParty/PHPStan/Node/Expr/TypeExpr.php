<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('typeExpr')]
final class TypeExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $exprType;
    /** @api */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $exprType
    )
    {
        $this->exprType = $exprType;
        parent::__construct();
    }
    public function getExprType(): Type
    {
        return $this->exprType;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_TypeExpr';
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
