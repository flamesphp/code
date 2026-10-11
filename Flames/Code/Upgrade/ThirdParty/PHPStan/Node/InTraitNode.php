<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('inTraitNode')]
final class InTraitNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Stmt\Trait_ $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $traitReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $implementingClassReflection;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node\Stmt\Trait_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $traitReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $implementingClassReflection
    )
    {
        $this->originalNode = $originalNode;
        $this->traitReflection = $traitReflection;
        $this->implementingClassReflection = $implementingClassReflection;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): Node\Stmt\Trait_
    {
        return $this->originalNode;
    }
    public function getTraitReflection(): ClassReflection
    {
        return $this->traitReflection;
    }
    public function getImplementingClassReflection(): ClassReflection
    {
        return $this->implementingClassReflection;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Stmt_InTraitNode';
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
