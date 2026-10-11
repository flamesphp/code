<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('classMethodsNode')]
final class ClassMethodsNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassLike $class;
    /**
     * @var ClassMethod[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $methods;
    /**
     * @var array<int, MethodCall>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $methodCalls;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    /**
     * @param ClassMethod[] $methods
     * @param array<int, MethodCall> $methodCalls
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassLike $class,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $methods,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $methodCalls,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection
    )
    {
        $this->class = $class;
        $this->methods = $methods;
        $this->methodCalls = $methodCalls;
        $this->classReflection = $classReflection;
        parent::__construct($class->getAttributes());
    }
    public function getClass(): ClassLike
    {
        return $this->class;
    }
    /**
     * @return ClassMethod[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
    /**
     * @return array<int, MethodCall>
     */
    public function getMethodCalls(): array
    {
        return $this->methodCalls;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClassMethodsNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
}
