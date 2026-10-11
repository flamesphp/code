<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('varTagChangedExpressionTypeNode')]
final class VarTagChangedExpressionTypeNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private VarTag $varTag;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $expr;
    public function __construct(
        VarTag $varTag,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $expr
    )
    {
        $this->varTag = $varTag;
        $this->expr = $expr;
        parent::__construct($expr->getAttributes());
    }
    public function getVarTag(): VarTag
    {
        return $this->varTag;
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_VarTagChangedExpressionType';
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
