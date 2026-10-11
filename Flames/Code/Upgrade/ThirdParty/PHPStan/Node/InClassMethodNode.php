<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('inClassMethodNode')]
final class InClassMethodNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpMethodFromParserNodeReflection $methodReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Stmt\ClassMethod $originalNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpMethodFromParserNodeReflection $methodReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node\Stmt\ClassMethod $originalNode
    )
    {
        $this->classReflection = $classReflection;
        $this->methodReflection = $methodReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getMethodReflection(): PhpMethodFromParserNodeReflection
    {
        return $this->methodReflection;
    }
    public function getOriginalNode(): Node\Stmt\ClassMethod
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Stmt_InClassMethodNode';
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
