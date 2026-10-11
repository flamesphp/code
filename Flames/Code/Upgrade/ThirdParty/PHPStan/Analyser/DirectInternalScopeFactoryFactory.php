<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ExpressionTypeResolverExtension;
final class DirectInternalScopeFactoryFactory implements \PHPStan\Analyser\InternalScopeFactoryFactory
{
    private Container $container;
    private ReflectionProvider $reflectionProvider;
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    /**
     * @var ExtensionsCollection<ExpressionTypeResolverExtension>
     */
    private ExtensionsCollection $expressionTypeResolverExtensions;
    private ExprPrinter $exprPrinter;
    private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;
    private PropertyReflectionFinder $propertyReflectionFinder;
    private Parser $parser;
    private PhpVersion $phpVersion;
    private AttributeReflectionFactory $attributeReflectionFactory;
    private ConfiguredPhpVersionRangeHelper $configuredPhpVersionRangeHelper;
    private \PHPStan\Analyser\ConstantResolver $constantResolver;
    /**
     * @param ExtensionsCollection<ExpressionTypeResolverExtension> $expressionTypeResolverExtensions
     */
    public function __construct(Container $container, ReflectionProvider $reflectionProvider, InitializerExprTypeResolver $initializerExprTypeResolver, ExtensionsCollection $expressionTypeResolverExtensions, ExprPrinter $exprPrinter, \PHPStan\Analyser\TypeSpecifier $typeSpecifier, PropertyReflectionFinder $propertyReflectionFinder, Parser $parser, PhpVersion $phpVersion, AttributeReflectionFactory $attributeReflectionFactory, ConfiguredPhpVersionRangeHelper $configuredPhpVersionRangeHelper, \PHPStan\Analyser\ConstantResolver $constantResolver)
    {
        $this->container = $container;
        $this->reflectionProvider = $reflectionProvider;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->expressionTypeResolverExtensions = $expressionTypeResolverExtensions;
        $this->exprPrinter = $exprPrinter;
        $this->typeSpecifier = $typeSpecifier;
        $this->propertyReflectionFinder = $propertyReflectionFinder;
        $this->parser = $parser;
        $this->phpVersion = $phpVersion;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->configuredPhpVersionRangeHelper = $configuredPhpVersionRangeHelper;
        $this->constantResolver = $constantResolver;
    }
    /**
     * @param callable(Node $node, Scope $scope): void|null $nodeCallback
     */
    public function create(?callable $nodeCallback): \PHPStan\Analyser\DirectInternalScopeFactory
    {
        return new \PHPStan\Analyser\DirectInternalScopeFactory($this->container, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->expressionTypeResolverExtensions, $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->parser, $this->phpVersion, $this->attributeReflectionFactory, $this->configuredPhpVersionRangeHelper, $nodeCallback, $this->constantResolver);
    }
}
