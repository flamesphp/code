<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('noopExpressionNode')]
final class NoopExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $originalExpr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $hasAssign;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $originalExpr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $hasAssign
    )
    {
        $this->originalExpr = $originalExpr;
        $this->hasAssign = $hasAssign;
        parent::__construct($this->originalExpr->getAttributes());
    }
    public function getOriginalExpr(): Expr
    {
        return $this->originalExpr;
    }
    public function hasAssign(): bool
    {
        return $this->hasAssign;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_NoopExpressionNode';
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
