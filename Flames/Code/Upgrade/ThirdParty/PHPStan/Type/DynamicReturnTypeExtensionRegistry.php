<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use function array_merge;
use function strtolower;
#[AutowiredService]
final class DynamicReturnTypeExtensionRegistry
{
    private ReflectionProvider $reflectionProvider;
    /**
     * @var ExtensionsCollection<DynamicMethodReturnTypeExtension>
     */
    private ExtensionsCollection $dynamicMethodReturnTypeExtensions;
    /**
     * @var ExtensionsCollection<DynamicStaticMethodReturnTypeExtension>
     */
    private ExtensionsCollection $dynamicStaticMethodReturnTypeExtensions;
    /**
     * @var ExtensionsCollection<DynamicFunctionReturnTypeExtension>
     */
    private ExtensionsCollection $dynamicFunctionReturnTypeExtensions;
    /** @var DynamicMethodReturnTypeExtension[][]|null */
    private ?array $dynamicMethodReturnTypeExtensionsByClass = null;
    /** @var DynamicStaticMethodReturnTypeExtension[][]|null */
    private ?array $dynamicStaticMethodReturnTypeExtensionsByClass = null;
    /** @var array<string, list<DynamicFunctionReturnTypeExtension>> */
    private array $dynamicReturnTypeExtensionsByFunction = [];
    /**
     * @param ExtensionsCollection<DynamicMethodReturnTypeExtension> $dynamicMethodReturnTypeExtensions
     * @param ExtensionsCollection<DynamicStaticMethodReturnTypeExtension> $dynamicStaticMethodReturnTypeExtensions
     * @param ExtensionsCollection<DynamicFunctionReturnTypeExtension> $dynamicFunctionReturnTypeExtensions
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        #[AutowiredExtensions(\PHPStan\Type\DynamicMethodReturnTypeExtension::class)]
        ExtensionsCollection $dynamicMethodReturnTypeExtensions,
        #[AutowiredExtensions(\PHPStan\Type\DynamicStaticMethodReturnTypeExtension::class)]
        ExtensionsCollection $dynamicStaticMethodReturnTypeExtensions,
        #[AutowiredExtensions(\PHPStan\Type\DynamicFunctionReturnTypeExtension::class)]
        ExtensionsCollection $dynamicFunctionReturnTypeExtensions
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->dynamicMethodReturnTypeExtensions = $dynamicMethodReturnTypeExtensions;
        $this->dynamicStaticMethodReturnTypeExtensions = $dynamicStaticMethodReturnTypeExtensions;
        $this->dynamicFunctionReturnTypeExtensions = $dynamicFunctionReturnTypeExtensions;
    }
    /**
     * @return DynamicMethodReturnTypeExtension[]
     */
    public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
    {
        if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicMethodReturnTypeExtensions->getAll() as $extension) {
                $byClass[strtolower($extension->getClass())][] = $extension;
            }
            $this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @return DynamicStaticMethodReturnTypeExtension[]
     */
    public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
    {
        if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicStaticMethodReturnTypeExtensions->getAll() as $extension) {
                $byClass[strtolower($extension->getClass())][] = $extension;
            }
            $this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @param DynamicMethodReturnTypeExtension[][]|DynamicStaticMethodReturnTypeExtension[][] $extensions
     * @return mixed[]
     */
    private function getDynamicExtensionsForType(array $extensions, string $className): array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }
        $extensionsForClass = [[]];
        $extensionClassNames = \PHPStan\Type\ExtensionClassHelper::getExtensionClassNames($this->reflectionProvider, $className);
        foreach ($extensionClassNames as $extensionClassName) {
            $extensionClassName = strtolower($extensionClassName);
            if (!isset($extensions[$extensionClassName])) {
                continue;
            }
            $extensionsForClass[] = $extensions[$extensionClassName];
        }
        return array_merge(...$extensionsForClass);
    }
    /**
     * @return DynamicFunctionReturnTypeExtension[]
     */
    public function getDynamicFunctionReturnTypeExtensions(FunctionReflection $functionReflection): array
    {
        $functionName = $functionReflection->getName();
        if (isset($this->dynamicReturnTypeExtensionsByFunction[$functionName])) {
            return $this->dynamicReturnTypeExtensionsByFunction[$functionName];
        }
        $supportedFunctions = [];
        foreach ($this->dynamicFunctionReturnTypeExtensions->getAll() as $dynamicFunctionReturnTypeExtension) {
            if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
                continue;
            }
            $supportedFunctions[] = $dynamicFunctionReturnTypeExtension;
        }
        return $this->dynamicReturnTypeExtensionsByFunction[$functionName] = $supportedFunctions;
    }
}
