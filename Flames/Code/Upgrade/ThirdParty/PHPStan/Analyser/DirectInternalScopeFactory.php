<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ExpressionTypeResolverExtension;
final class DirectInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
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
    /**
     * @var callable(Node $node, Scope $scope): void|null
     */
    private $nodeCallback;
    private \PHPStan\Analyser\ConstantResolver $constantResolver;
    private bool $createsNodeCallbackScopes;
    private \PHPStan\Analyser\ExpressionResultStorageStack $expressionResultStorageStack;
    /**
     * @param callable(Node $node, Scope $scope): void|null $nodeCallback
     * @param ExtensionsCollection<ExpressionTypeResolverExtension> $expressionTypeResolverExtensions
     */
    public function __construct(Container $container, ReflectionProvider $reflectionProvider, InitializerExprTypeResolver $initializerExprTypeResolver, ExtensionsCollection $expressionTypeResolverExtensions, ExprPrinter $exprPrinter, \PHPStan\Analyser\TypeSpecifier $typeSpecifier, PropertyReflectionFinder $propertyReflectionFinder, Parser $parser, PhpVersion $phpVersion, AttributeReflectionFactory $attributeReflectionFactory, ConfiguredPhpVersionRangeHelper $configuredPhpVersionRangeHelper, $nodeCallback, \PHPStan\Analyser\ConstantResolver $constantResolver, bool $createsNodeCallbackScopes = \false, ?\PHPStan\Analyser\ExpressionResultStorageStack $expressionResultStorageStack = null)
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
        $this->nodeCallback = $nodeCallback;
        $this->constantResolver = $constantResolver;
        $this->createsNodeCallbackScopes = $createsNodeCallbackScopes;
        $this->expressionResultStorageStack = $expressionResultStorageStack ?? new \PHPStan\Analyser\ExpressionResultStorageStack();
    }
    /**
     * @param \PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection|null $function
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, bool $declareStrictTypes = \false, $function = null, ?string $namespace = null, array $expressionTypes = [], array $nativeExpressionTypes = [], array $conditionalExpressions = [], array $inClosureBindScopeClasses = [], ?ClosureType $anonymousFunctionReflection = null, bool $inFirstLevelStatement = \true, array $currentlyAssignedExpressions = [], array $currentlyAllowedUndefinedExpressions = [], array $inFunctionCallsStack = [], bool $afterExtractCall = \false, ?\PHPStan\Analyser\MutatingScope $parentScope = null, bool $nativeTypesPromoted = \false, ?TemplateArgumentFrame $templateArgumentFrame = null, ?TemplateArgumentConstraints $templateArgumentConstraints = null): \PHPStan\Analyser\MutatingScope
    {
        $className = \PHPStan\Analyser\MutatingScope::class;
        if ($this->createsNodeCallbackScopes) {
            $className = \PHPStan\Analyser\NodeCallbackScope::class;
        }
        return new $className($this->container, $this, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->expressionTypeResolverExtensions, $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->parser, $this->constantResolver, $this->expressionResultStorageStack, $context, $this->phpVersion, $this->attributeReflectionFactory, $this->configuredPhpVersionRangeHelper, $this->nodeCallback, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted, $templateArgumentFrame, $templateArgumentConstraints);
    }
    public function toNodeCallbackScopeFactory(): \PHPStan\Analyser\InternalScopeFactory
    {
        return $this->withFlavor(\true);
    }
    public function toWalkScopeFactory(): \PHPStan\Analyser\InternalScopeFactory
    {
        return $this->withFlavor(\false);
    }
    private function withFlavor(bool $createsNodeCallbackScopes): self
    {
        return new self($this->container, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->expressionTypeResolverExtensions, $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->parser, $this->phpVersion, $this->attributeReflectionFactory, $this->configuredPhpVersionRangeHelper, $this->nodeCallback, $this->constantResolver, $createsNodeCallbackScopes, $this->expressionResultStorageStack);
    }
}
