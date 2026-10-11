<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('classConstantsNode')]
final class ClassConstantsNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassLike $class;
    /**
     * @var ClassConst[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $constants;
    /**
     * @var ClassConstantFetch[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $fetches;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    /**
     * @param ClassConst[] $constants
     * @param ClassConstantFetch[] $fetches
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassLike $class,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $constants,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $fetches,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection
    )
    {
        $this->class = $class;
        $this->constants = $constants;
        $this->fetches = $fetches;
        $this->classReflection = $classReflection;
        parent::__construct($class->getAttributes());
    }
    public function getClass(): ClassLike
    {
        return $this->class;
    }
    /**
     * @return ClassConst[]
     */
    public function getConstants(): array
    {
        return $this->constants;
    }
    /**
     * @return ClassConstantFetch[]
     */
    public function getFetches(): array
    {
        return $this->fetches;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClassConstantsNode';
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
