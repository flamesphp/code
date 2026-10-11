<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('possiblyImpureCallExpr')]
final class PossiblyImpureCallExpr extends Expr implements VirtualNode
{
    public Expr $callExpr;
    public Expr $impactedExpr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $callDescription;
    public function __construct(
        Expr $callExpr,
        Expr $impactedExpr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $callDescription
    )
    {
        $this->callExpr = $callExpr;
        $this->impactedExpr = $impactedExpr;
        $this->callDescription = $callDescription;
        parent::__construct([]);
    }
    public function getCallDescription(): string
    {
        return $this->callDescription;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_PossiblyImpureCallExpr';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return ['callExpr', 'impactedExpr'];
    }
}
