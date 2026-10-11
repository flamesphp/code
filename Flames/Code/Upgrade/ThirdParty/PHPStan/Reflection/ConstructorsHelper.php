<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use ReflectionException;
use function array_key_exists;
use function explode;
#[AutowiredService]
final class ConstructorsHelper
{
    /**
     * @var ExtensionsCollection<AdditionalConstructorsExtension>
     */
    private ExtensionsCollection $additionalConstructorsExtensions;
    /**
     * @var list<string>
     */
    private array $additionalConstructors;
    /** @var array<string, list<string>> */
    private array $additionalConstructorsCache = [];
    /**
     * @param ExtensionsCollection<AdditionalConstructorsExtension> $additionalConstructorsExtensions
     * @param list<string> $additionalConstructors
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Reflection\AdditionalConstructorsExtension::class)]
        ExtensionsCollection $additionalConstructorsExtensions,
        #[AutowiredParameter]
        array $additionalConstructors
    )
    {
        $this->additionalConstructorsExtensions = $additionalConstructorsExtensions;
        $this->additionalConstructors = $additionalConstructors;
    }
    /**
     * @return list<string>
     */
    public function getConstructors(\PHPStan\Reflection\ClassReflection $classReflection): array
    {
        if (array_key_exists($classReflection->getName(), $this->additionalConstructorsCache)) {
            return $this->additionalConstructorsCache[$classReflection->getName()];
        }
        $constructors = [];
        if ($classReflection->hasConstructor()) {
            $constructors[] = $classReflection->getConstructor()->getName();
        }
        $extensions = $this->additionalConstructorsExtensions->getAll();
        foreach ($extensions as $extension) {
            $extensionConstructors = $extension->getAdditionalConstructors($classReflection);
            foreach ($extensionConstructors as $extensionConstructor) {
                $constructors[] = $extensionConstructor;
            }
        }
        $nativeReflection = $classReflection->getNativeReflection();
        foreach ($this->additionalConstructors as $additionalConstructor) {
            [$className, $methodName] = explode('::', $additionalConstructor);
            if (!$nativeReflection->hasMethod($methodName)) {
                continue;
            }
            $nativeMethod = $nativeReflection->getMethod($methodName);
            if ($nativeMethod->getDeclaringClass()->betterReflectionClass->getName() !== $nativeReflection->getName()) {
                continue;
            }
            try {
                $prototype = $nativeMethod->getPrototype();
            } catch (ReflectionException $e) {
                $prototype = $nativeMethod;
            }
            if ($prototype->getDeclaringClass()->betterReflectionClass->getName() !== $className) {
                continue;
            }
            $constructors[] = $methodName;
        }
        $this->additionalConstructorsCache[$classReflection->getName()] = $constructors;
        return $constructors;
    }
}
