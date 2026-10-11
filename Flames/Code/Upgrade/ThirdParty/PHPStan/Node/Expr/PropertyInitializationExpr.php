<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('propertyInitializationExpr')]
final class PropertyInitializationExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $propertyName;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $propertyName
    )
    {
        $this->propertyName = $propertyName;
        parent::__construct([]);
    }
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_PropertyInitializationExpr';
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
