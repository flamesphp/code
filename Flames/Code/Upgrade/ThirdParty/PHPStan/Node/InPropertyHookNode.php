<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('inPropertyHookNode')]
final class InPropertyHookNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpMethodFromParserNodeReflection $hookReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpPropertyReflection $propertyReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\PropertyHook $originalNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpMethodFromParserNodeReflection $hookReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpPropertyReflection $propertyReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node\PropertyHook $originalNode
    )
    {
        $this->classReflection = $classReflection;
        $this->hookReflection = $hookReflection;
        $this->propertyReflection = $propertyReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getHookReflection(): PhpMethodFromParserNodeReflection
    {
        return $this->hookReflection;
    }
    public function getPropertyReflection(): PhpPropertyReflection
    {
        return $this->propertyReflection;
    }
    public function getOriginalNode(): Node\PropertyHook
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_InPropertyHookNode';
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
