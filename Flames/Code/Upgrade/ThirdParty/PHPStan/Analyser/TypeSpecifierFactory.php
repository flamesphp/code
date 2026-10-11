<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use function array_merge;
#[AutowiredService('typeSpecifierFactory')]
final class TypeSpecifierFactory
{
    private Container $container;
    public const FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.functionTypeSpecifyingExtension';
    public const METHOD_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.methodTypeSpecifyingExtension';
    public const STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension';
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function create(): \PHPStan\Analyser\TypeSpecifier
    {
        $functionTypeSpecifying = $this->container->getExtensionsCollection(FunctionTypeSpecifyingExtension::class)->getAll();
        $methodTypeSpecifying = $this->container->getExtensionsCollection(MethodTypeSpecifyingExtension::class)->getAll();
        $staticMethodTypeSpecifying = $this->container->getExtensionsCollection(StaticMethodTypeSpecifyingExtension::class)->getAll();
        $typeSpecifier = new \PHPStan\Analyser\TypeSpecifier($this->container->getByType(ExprPrinter::class), $this->container->getByType(ReflectionProvider::class), $functionTypeSpecifying, $methodTypeSpecifying, $staticMethodTypeSpecifying, $this->container->getParameter('rememberPossiblyImpureFunctionValues'), $this->container);
        foreach (array_merge($this->container->getExtensionsCollection(PropertiesClassReflectionExtension::class)->getAll(), $this->container->getExtensionsCollection(MethodsClassReflectionExtension::class)->getAll(), $this->container->getExtensionsCollection(DynamicMethodReturnTypeExtension::class)->getAll(), $this->container->getExtensionsCollection(DynamicStaticMethodReturnTypeExtension::class)->getAll(), $this->container->getExtensionsCollection(DynamicFunctionReturnTypeExtension::class)->getAll(), $functionTypeSpecifying, $methodTypeSpecifying, $staticMethodTypeSpecifying) as $extension) {
            if (!$extension instanceof \PHPStan\Analyser\TypeSpecifierAwareExtension) {
                continue;
            }
            $extension->setTypeSpecifier($typeSpecifier);
        }
        return $typeSpecifier;
    }
}
