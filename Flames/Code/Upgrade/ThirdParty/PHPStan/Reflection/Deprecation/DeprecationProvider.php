<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
#[AutowiredService]
final class DeprecationProvider
{
    /**
     * @var ExtensionsCollection<ClassDeprecationExtension>
     */
    private ExtensionsCollection $classDeprecationExtensions;
    /**
     * @var ExtensionsCollection<ClassConstantDeprecationExtension>
     */
    private ExtensionsCollection $classConstantDeprecationExtensions;
    /**
     * @var ExtensionsCollection<ConstantDeprecationExtension>
     */
    private ExtensionsCollection $constantDeprecationExtensions;
    /**
     * @var ExtensionsCollection<EnumCaseDeprecationExtension>
     */
    private ExtensionsCollection $enumCaseDeprecationExtensions;
    /**
     * @var ExtensionsCollection<FunctionDeprecationExtension>
     */
    private ExtensionsCollection $functionDeprecationExtensions;
    /**
     * @var ExtensionsCollection<MethodDeprecationExtension>
     */
    private ExtensionsCollection $methodDeprecationExtensions;
    /**
     * @var ExtensionsCollection<PropertyDeprecationExtension>
     */
    private ExtensionsCollection $propertyDeprecationExtensions;
    /**
     * @param ExtensionsCollection<ClassDeprecationExtension> $classDeprecationExtensions
     * @param ExtensionsCollection<ClassConstantDeprecationExtension> $classConstantDeprecationExtensions
     * @param ExtensionsCollection<ConstantDeprecationExtension> $constantDeprecationExtensions
     * @param ExtensionsCollection<EnumCaseDeprecationExtension> $enumCaseDeprecationExtensions
     * @param ExtensionsCollection<FunctionDeprecationExtension> $functionDeprecationExtensions
     * @param ExtensionsCollection<MethodDeprecationExtension> $methodDeprecationExtensions
     * @param ExtensionsCollection<PropertyDeprecationExtension> $propertyDeprecationExtensions
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\ClassDeprecationExtension::class)]
        ExtensionsCollection $classDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\ClassConstantDeprecationExtension::class)]
        ExtensionsCollection $classConstantDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\ConstantDeprecationExtension::class)]
        ExtensionsCollection $constantDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\EnumCaseDeprecationExtension::class)]
        ExtensionsCollection $enumCaseDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\FunctionDeprecationExtension::class)]
        ExtensionsCollection $functionDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\MethodDeprecationExtension::class)]
        ExtensionsCollection $methodDeprecationExtensions,
        #[AutowiredExtensions(\PHPStan\Reflection\Deprecation\PropertyDeprecationExtension::class)]
        ExtensionsCollection $propertyDeprecationExtensions
    )
    {
        $this->classDeprecationExtensions = $classDeprecationExtensions;
        $this->classConstantDeprecationExtensions = $classConstantDeprecationExtensions;
        $this->constantDeprecationExtensions = $constantDeprecationExtensions;
        $this->enumCaseDeprecationExtensions = $enumCaseDeprecationExtensions;
        $this->functionDeprecationExtensions = $functionDeprecationExtensions;
        $this->methodDeprecationExtensions = $methodDeprecationExtensions;
        $this->propertyDeprecationExtensions = $propertyDeprecationExtensions;
    }
    public function getPropertyDeprecation(ReflectionProperty $reflectionProperty): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->propertyDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getPropertyDeprecation($reflectionProperty);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getMethodDeprecation(ReflectionMethod $methodReflection): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->methodDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getMethodDeprecation($methodReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getClassConstantDeprecation(ReflectionClassConstant $reflectionConstant): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->classConstantDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getClassConstantDeprecation($reflectionConstant);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum $reflection
     */
    public function getClassDeprecation($reflection): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->classDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getClassDeprecation($reflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getFunctionDeprecation(ReflectionFunction $reflectionFunction): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->functionDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getFunctionDeprecation($reflectionFunction);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getConstantDeprecation(ReflectionConstant $constantReflection): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->constantDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getConstantDeprecation($constantReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase $enumCaseReflection
     */
    public function getEnumCaseDeprecation($enumCaseReflection): ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        foreach ($this->enumCaseDeprecationExtensions->getAll() as $extension) {
            $deprecation = $extension->getEnumCaseDeprecation($enumCaseReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
}
