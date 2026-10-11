<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use WeakReference;
#[GenerateFactory(\PHPStan\Analyser\InternalScopeFactoryFactory::class, \PHPStan\Analyser\LazyInternalScopeFactory::class)]
#[ReferencedByTurboExtension('lazyInternalScopeFactory')]
final class LazyInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
{
    private Container $container;
    /**
     * @var callable(Node $node, Scope $scope): void|null
     */
    private $nodeCallback;
    private bool $createsNodeCallbackScopes;
    private Parser $currentSimpleVersionParser;
    private ?ReflectionProvider $reflectionProvider = null;
    private ?InitializerExprTypeResolver $initializerExprTypeResolver = null;
    /** @var ExtensionsCollection<ExpressionTypeResolverExtension>|null */
    private ?ExtensionsCollection $expressionTypeResolverExtensions = null;
    private ?ExprPrinter $exprPrinter = null;
    private ?\PHPStan\Analyser\TypeSpecifier $typeSpecifier = null;
    private ?PropertyReflectionFinder $propertyReflectionFinder = null;
    private ?\PHPStan\Analyser\ConstantResolver $constantResolver = null;
    private \PHPStan\Analyser\ExpressionResultStorageStack $expressionResultStorageStack;
    private ?PhpVersion $phpVersionType = null;
    private ?AttributeReflectionFactory $attributeReflectionFactory = null;
    private ?ConfiguredPhpVersionRangeHelper $configuredPhpVersionRangeHelper = null;
    private ?self $twin = null;
    /** @var WeakReference<self>|null */
    private ?WeakReference $origin = null;
    /**
     * @param callable(Node $node, Scope $scope): void|null $nodeCallback
     */
    public function __construct(Container $container, $nodeCallback, bool $createsNodeCallbackScopes = \false, ?\PHPStan\Analyser\ExpressionResultStorageStack $expressionResultStorageStack = null)
    {
        $this->container = $container;
        $this->nodeCallback = $nodeCallback;
        $this->createsNodeCallbackScopes = $createsNodeCallbackScopes;
        $this->currentSimpleVersionParser = $this->container->getService('currentPhpVersionSimpleParser');
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
        $this->reflectionProvider ??= $this->container->getByType(ReflectionProvider::class);
        $this->initializerExprTypeResolver ??= $this->container->getByType(InitializerExprTypeResolver::class);
        $this->expressionTypeResolverExtensions ??= $this->container->getExtensionsCollection(ExpressionTypeResolverExtension::class);
        $this->exprPrinter ??= $this->container->getByType(ExprPrinter::class);
        $this->typeSpecifier ??= $this->container->getByType(\PHPStan\Analyser\TypeSpecifier::class);
        $this->propertyReflectionFinder ??= $this->container->getByType(PropertyReflectionFinder::class);
        $this->constantResolver ??= $this->container->getByType(\PHPStan\Analyser\ConstantResolver::class);
        $this->phpVersionType ??= $this->container->getByType(PhpVersion::class);
        $this->attributeReflectionFactory ??= $this->container->getByType(AttributeReflectionFactory::class);
        $this->configuredPhpVersionRangeHelper ??= $this->container->getByType(ConfiguredPhpVersionRangeHelper::class);
        return new $className($this->container, $this, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->expressionTypeResolverExtensions, $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->currentSimpleVersionParser, $this->constantResolver, $this->expressionResultStorageStack, $context, $this->phpVersionType, $this->attributeReflectionFactory, $this->configuredPhpVersionRangeHelper, $this->nodeCallback, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted, $templateArgumentFrame, $templateArgumentConstraints);
    }
    public function toNodeCallbackScopeFactory(): \PHPStan\Analyser\InternalScopeFactory
    {
        return $this->createsNodeCallbackScopes ? $this : $this->twin();
    }
    public function toWalkScopeFactory(): \PHPStan\Analyser\InternalScopeFactory
    {
        return $this->createsNodeCallbackScopes ? $this->twin() : $this;
    }
    /**
     * The factory for the other scope flavour, created once. Scopes switch
     * flavour constantly, and a fresh factory would start with empty memos —
     * resolving every service above out of the container again on its first
     * create().
     *
     * The pair is held one way strongly and the other way weakly: a factory
     * belongs to a single analysed file (ScopeFactory::create() makes one per
     * file, closing over that file's node callback), and a strong cycle here
     * would keep every file's callback alive for the whole run — PHPStan runs
     * with the cycle collector disabled, so nothing would ever free it.
     */
    private function twin(): self
    {
        if ($this->twin !== null) {
            return $this->twin;
        }
        if ($this->origin !== null) {
            $origin = $this->origin->get();
            if ($origin !== null) {
                return $origin;
            }
        }
        $this->twin = new self($this->container, $this->nodeCallback, !$this->createsNodeCallbackScopes, $this->expressionResultStorageStack);
        $this->twin->origin = WeakReference::create($this);
        return $this->twin;
    }
}
