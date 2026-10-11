<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('foreachValueByRefExpr')]
final class ForeachValueByRefExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $expr;
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
        return 'PHPStan_Node_ForeachValueByRefExpr';
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
