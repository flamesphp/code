<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('inClassNode')]
final class InClassNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassLike $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassLike $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection
    )
    {
        $this->originalNode = $originalNode;
        $this->classReflection = $classReflection;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): ClassLike
    {
        return $this->originalNode;
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Stmt_InClassNode';
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
