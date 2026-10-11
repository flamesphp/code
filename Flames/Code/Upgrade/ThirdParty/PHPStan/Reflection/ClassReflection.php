<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use Attribute;
use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\PhpDoc\Tag\RequireImplementsTag;
use PHPStan\PhpDoc\Tag\SealedTypeTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\CircularTypeAliasDefinitionException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Generic\TypeProjectionHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAlias;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_bool;
use function is_file;
use function is_int;
use function sprintf;
use function strtolower;
/**
 * @api
 */
#[GenerateFactory(\PHPStan\Reflection\ClassReflectionFactory::class)]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ClassReflection.cpp')]
final class ClassReflection
{
    private \PHPStan\Reflection\ClassReflectionFactory $classReflectionFactory;
    private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;
    private \PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver;
    private FileTypeMapper $fileTypeMapper;
    private StubPhpDocProvider $stubPhpDocProvider;
    private PhpDocInheritanceResolver $phpDocInheritanceResolver;
    private PhpVersion $phpVersion;
    private SignatureMapProvider $signatureMapProvider;
    private DeprecationProvider $deprecationProvider;
    private \PHPStan\Reflection\AttributeReflectionFactory $attributeReflectionFactory;
    private ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider;
    private string $displayName;
    /**
     * @var ReflectionClass|ReflectionEnum
     */
    private CoreReflectionClass $reflection;
    private ?string $anonymousFilename;
    private ?TemplateTypeMap $resolvedTemplateTypeMap;
    /**
     * @var Closure(): ?ResolvedPhpDocBlock|null
     */
    private ?Closure $stubPhpDocBlockCallback;
    private ?string $extraCacheKey;
    private ?TemplateTypeVarianceMap $resolvedCallSiteVarianceMap;
    private ?bool $finalByKeywordOverride;
    /** @var ExtendedMethodReflection[] */
    private array $methods = [];
    /** @var ExtendedPropertyReflection[] */
    private array $properties = [];
    /** @var ExtendedPropertyReflection[] */
    private array $instanceProperties = [];
    /** @var ExtendedPropertyReflection[] */
    private array $staticProperties = [];
    /** @var RealClassClassConstantReflection[] */
    private array $constants = [];
    /** @var EnumCaseReflection[]|null  */
    private ?array $enumCases = null;
    /** @var int[]|null */
    private ?array $classHierarchyDistances = null;
    private ?string $deprecatedDescription = null;
    private ?bool $isDeprecated = null;
    /** @var array<Type>|null */
    private ?array $allowedSubTypes = null;
    private bool $allowedSubTypesResolved = \false;
    private ?bool $isGeneric = null;
    private ?bool $isInternal = null;
    private ?bool $isFinal = null;
    private ?bool $isImmutable = null;
    private ?bool $hasConsistentConstructor = null;
    private ?bool $acceptsNamedArguments = null;
    private ?TemplateTypeMap $templateTypeMap = null;
    private ?TemplateTypeMap $activeTemplateTypeMap = null;
    private ?TemplateTypeVarianceMap $defaultCallSiteVarianceMap = null;
    private ?TemplateTypeVarianceMap $callSiteVarianceMap = null;
    /** @var array<string,ClassReflection>|null */
    private ?array $ancestors = null;
    private ?string $cacheKey = null;
    /** @var array<string, bool> */
    private array $subclasses = [];
    /**
     * @var string|false|null
     */
    private $filename = \false;
    /**
     * @var string|false|null
     */
    private $reflectionDocComment = \false;
    /**
     * @var false|\PHPStan\PhpDoc\ResolvedPhpDocBlock|null
     */
    private $stubPhpDocBlock = \false;
    /**
     * @var false|\PHPStan\PhpDoc\ResolvedPhpDocBlock
     */
    private $resolvedPhpDocBlock = \false;
    /**
     * @var false|\PHPStan\PhpDoc\ResolvedPhpDocBlock
     */
    private $traitContextResolvedPhpDocBlock = \false;
    /** @var array<string, ClassReflection>|null */
    private ?array $cachedInterfaces = null;
    /**
     * @var \PHPStan\Reflection\ClassReflection|false|null
     */
    private $cachedParentClass = \false;
    /**
     * Name of the class the parent class chain loops back to, false when it has not been looked for yet.
     *
     * @var class-string|false|null
     */
    private $circularParentClassName = \false;
    /** @var array<string, TypeAlias>|null */
    private ?array $typeAliases = null;
    /** @var array<string, true> */
    private static array $resolvingTypeAliasImports = [];
    /** @var array<string, bool> */
    private array $hasMethodCache = [];
    /** @var array<string, bool> */
    private array $hasPropertyCache = [];
    /** @var array<string, bool> */
    private array $hasInstancePropertyCache = [];
    /** @var array<string, bool> */
    private array $hasStaticPropertyCache = [];
    /**
     * getName() is the hottest call on this class (millions per run), two adapter frames deep
     *
     * @var class-string|null
     */
    private ?string $name = null;
    /**
     * @param ReflectionClass|ReflectionEnum $reflection
     * @param (Closure(): ?ResolvedPhpDocBlock)|null $stubPhpDocBlockCallback
     */
    public function __construct(\PHPStan\Reflection\ClassReflectionFactory $classReflectionFactory, \PHPStan\Reflection\ReflectionProvider $reflectionProvider, \PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver, FileTypeMapper $fileTypeMapper, StubPhpDocProvider $stubPhpDocProvider, PhpDocInheritanceResolver $phpDocInheritanceResolver, PhpVersion $phpVersion, SignatureMapProvider $signatureMapProvider, DeprecationProvider $deprecationProvider, \PHPStan\Reflection\AttributeReflectionFactory $attributeReflectionFactory, ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider, string $displayName, CoreReflectionClass $reflection, ?string $anonymousFilename, ?TemplateTypeMap $resolvedTemplateTypeMap, ?Closure $stubPhpDocBlockCallback, ?string $extraCacheKey = null, ?TemplateTypeVarianceMap $resolvedCallSiteVarianceMap = null, ?bool $finalByKeywordOverride = null)
    {
        $this->classReflectionFactory = $classReflectionFactory;
        $this->reflectionProvider = $reflectionProvider;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->stubPhpDocProvider = $stubPhpDocProvider;
        $this->phpDocInheritanceResolver = $phpDocInheritanceResolver;
        $this->phpVersion = $phpVersion;
        $this->signatureMapProvider = $signatureMapProvider;
        $this->deprecationProvider = $deprecationProvider;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->classReflectionExtensionRegistryProvider = $classReflectionExtensionRegistryProvider;
        $this->displayName = $displayName;
        $this->reflection = $reflection;
        $this->anonymousFilename = $anonymousFilename;
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap;
        $this->stubPhpDocBlockCallback = $stubPhpDocBlockCallback;
        $this->extraCacheKey = $extraCacheKey;
        $this->resolvedCallSiteVarianceMap = $resolvedCallSiteVarianceMap;
        $this->finalByKeywordOverride = $finalByKeywordOverride;
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum
     */
    public function getNativeReflection()
    {
        return $this->reflection;
    }
    public function getFileName(): ?string
    {
        if (!is_bool($this->filename)) {
            return $this->filename;
        }
        if ($this->anonymousFilename !== null) {
            return $this->filename = $this->anonymousFilename;
        }
        $fileName = $this->reflection->getFileName();
        if ($fileName === \false) {
            return $this->filename = null;
        }
        if (!is_file($fileName)) {
            return $this->filename = null;
        }
        return $this->filename = $fileName;
    }
    public function getParentClass(): ?\PHPStan\Reflection\ClassReflection
    {
        if (!is_bool($this->cachedParentClass)) {
            return $this->cachedParentClass;
        }
        $parentClass = $this->reflection->getParentClass();
        if ($parentClass === \false) {
            return $this->cachedParentClass = null;
        }
        $circularParentClassName = $this->findCircularParentClassName($parentClass->betterReflectionClass->getName());
        if ($circularParentClassName !== null) {
            throw CircularReference::fromClassName($circularParentClassName);
        }
        $extendsTag = $this->getFirstExtendsTag();
        if ($extendsTag !== null && $this->isValidAncestorType($extendsTag->type, [$parentClass->betterReflectionClass->getName()])) {
            $extendedType = $extendsTag->type;
            if ($this->isGeneric()) {
                $extendedType = TemplateTypeHelper::resolveTemplateTypes($extendedType, $this->getActiveTemplateTypeMapForAncestorResolution(), $this->getCallSiteVarianceMap(), TemplateTypeVariance::createStatic());
            }
            if (!$extendedType instanceof GenericObjectType) {
                return $this->reflectionProvider->getClass($parentClass->betterReflectionClass->getName());
            }
            return $extendedType->getClassReflection() ?? $this->reflectionProvider->getClass($parentClass->betterReflectionClass->getName());
        }
        $parentReflection = $this->reflectionProvider->getClass($parentClass->betterReflectionClass->getName());
        if ($parentReflection->isGeneric()) {
            return $parentReflection->withTypes(array_values($parentReflection->getTemplateTypeMap()->map(static fn(): Type => new ErrorType())->getTypes()));
        }
        $this->cachedParentClass = $parentReflection;
        return $parentReflection;
    }
    /**
     * BetterReflection only rejects a class extending itself directly. A cycle spanning
     * several classes (A extends B, B extends A) reaches this class intact and would make
     * every walk over the class hierarchy run forever, so it is detected here instead.
     *
     * @param class-string $parentClassName
     * @return class-string|null Name of the class the chain loops back to
     */
    private function findCircularParentClassName(string $parentClassName): ?string
    {
        if (!is_bool($this->circularParentClassName)) {
            return $this->circularParentClassName;
        }
        $this->circularParentClassName = null;
        $visitedClassNames = [strtolower($this->getName()) => \true];
        $currentClassName = $parentClassName;
        while (\true) {
            $lowercasedClassName = strtolower($currentClassName);
            if (array_key_exists($lowercasedClassName, $visitedClassNames)) {
                return $this->circularParentClassName = $currentClassName;
            }
            $visitedClassNames[$lowercasedClassName] = \true;
            if (!$this->reflectionProvider->hasClass($currentClassName)) {
                return null;
            }
            $parentClass = $this->reflectionProvider->getClass($currentClassName)->reflection->getParentClass();
            if ($parentClass === \false) {
                return null;
            }
            $currentClassName = $parentClass->betterReflectionClass->getName();
        }
    }
    /**
     * @return class-string
     */
    public function getName(): string
    {
        return $this->name ??= $this->reflection->getName();
    }
    public function getDisplayName(bool $withTemplateTypes = \true): string
    {
        if ($withTemplateTypes === \false || $this->resolvedTemplateTypeMap === null || count($this->resolvedTemplateTypeMap->getTypes()) === 0) {
            return $this->displayName;
        }
        $templateTypes = [];
        $variances = $this->getCallSiteVarianceMap()->getVariances();
        foreach ($this->getActiveTemplateTypeMap()->getTypes() as $name => $templateType) {
            $variance = $variances[$name] ?? null;
            if ($variance === null) {
                continue;
            }
            $templateTypes[] = TypeProjectionHelper::describe($templateType, $variance, VerbosityLevel::typeOnly());
        }
        return $this->displayName . '<' . implode(',', $templateTypes) . '>';
    }
    public function getCacheKey(): string
    {
        $cacheKey = $this->cacheKey;
        if ($cacheKey !== null) {
            return $this->cacheKey;
        }
        $cacheKey = $this->displayName;
        if ($this->resolvedTemplateTypeMap !== null) {
            $templateTypes = [];
            $variances = $this->getCallSiteVarianceMap()->getVariances();
            foreach ($this->getActiveTemplateTypeMap()->getTypes() as $name => $templateType) {
                $variance = $variances[$name] ?? null;
                if ($variance === null) {
                    continue;
                }
                $templateTypes[] = TypeProjectionHelper::describe($templateType, $variance, VerbosityLevel::cache());
            }
            $cacheKey .= '<' . implode(',', $templateTypes) . '>';
        }
        if ($this->hasFinalByKeywordOverride()) {
            $cacheKey .= '-f=' . ($this->isFinalByKeyword() ? 't' : 'f');
        }
        if ($this->extraCacheKey !== null) {
            $cacheKey .= '-' . $this->extraCacheKey;
        }
        $this->cacheKey = $cacheKey;
        return $cacheKey;
    }
    /**
     * @return int[]
     */
    public function getClassHierarchyDistances(): array
    {
        if ($this->classHierarchyDistances === null) {
            $distance = 0;
            $distances = [$this->getName() => $distance];
            $currentClassReflection = $this;
            foreach ($this->collectTraits($this->getNativeReflection()) as $trait) {
                $distance++;
                if (array_key_exists($trait->betterReflectionClass->getName(), $distances)) {
                    continue;
                }
                $distances[$trait->betterReflectionClass->getName()] = $distance;
            }
            // walking the parents through getParentClass() and not through the native
            // reflection makes a cyclic class hierarchy end in a CircularReference
            // exception instead of looping forever
            while (($currentClassReflection = $currentClassReflection->getParentClass()) !== null) {
                $distance++;
                $parentClassName = $currentClassReflection->getName();
                if (!array_key_exists($parentClassName, $distances)) {
                    $distances[$parentClassName] = $distance;
                }
                foreach ($this->collectTraits($currentClassReflection->getNativeReflection()) as $trait) {
                    $distance++;
                    if (array_key_exists($trait->betterReflectionClass->getName(), $distances)) {
                        continue;
                    }
                    $distances[$trait->betterReflectionClass->getName()] = $distance;
                }
            }
            foreach ($this->getNativeReflection()->getInterfaces() as $interface) {
                $distance++;
                if (array_key_exists($interface->betterReflectionClass->getName(), $distances)) {
                    continue;
                }
                $distances[$interface->betterReflectionClass->getName()] = $distance;
            }
            $this->classHierarchyDistances = $distances;
        }
        return $this->classHierarchyDistances;
    }
    /**
     * @return list<ReflectionClass>
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum $class
     */
    private function collectTraits($class): array
    {
        $traits = [];
        $traitsLeftToAnalyze = $class->getTraits();
        while (count($traitsLeftToAnalyze) !== 0) {
            $trait = array_shift($traitsLeftToAnalyze);
            // traits can use each other in a cycle - compare by name because
            // the reflection objects are not guaranteed to be identical
            if (array_key_exists($trait->betterReflectionClass->getName(), $traits)) {
                continue;
            }
            $traits[$trait->betterReflectionClass->getName()] = $trait;
            foreach ($trait->getTraits() as $subTrait) {
                $traitsLeftToAnalyze[] = $subTrait;
            }
        }
        return array_values($traits);
    }
    public function allowsDynamicProperties(): bool
    {
        if ($this->isEnum()) {
            return \false;
        }
        if (!($this->phpVersion->versionId >= 80200)) {
            return \true;
        }
        $hasMagicMethod = $this->hasNativeMethod('__get') || $this->hasNativeMethod('__set') || $this->hasNativeMethod('__isset');
        if ($hasMagicMethod) {
            return \true;
        }
        foreach ($this->getRequireExtendsTags() as $extendsTag) {
            $type = $extendsTag->type;
            if (!$type instanceof ObjectType) {
                continue;
            }
            $reflection = $type->getClassReflection();
            if ($reflection === null || !$reflection->allowsDynamicProperties()) {
                continue;
            }
            return \true;
        }
        if ($this->isReadOnly()) {
            return \false;
        }
        if (UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate($this->reflectionProvider, $this)) {
            return \true;
        }
        $class = $this;
        do {
            $attributes = $class->reflection->getAttributes('AllowDynamicProperties');
            $class = $class->getParentClass();
        } while ($attributes === [] && $class !== null);
        return $attributes !== [];
    }
    /**
     * @deprecated Use hasInstanceProperty or hasStaticProperty instead
     */
    public function hasProperty(string $propertyName): bool
    {
        if (array_key_exists($propertyName, $this->hasPropertyCache)) {
            return $this->hasPropertyCache[$propertyName];
        }
        if ($this->isEnum()) {
            return $this->hasPropertyCache[$propertyName] = $this->hasNativeProperty($propertyName);
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            return $this->hasPropertyCache[$propertyName] = \true;
        }
        if ($this->allowsDynamicProperties()) {
            foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->propertiesClassReflectionExtensions as $extension) {
                if ($extension->hasProperty($this, $propertyName)) {
                    return $this->hasPropertyCache[$propertyName] = \true;
                }
            }
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension->hasProperty($this, $propertyName)) {
            return $this->hasPropertyCache[$propertyName] = \true;
        }
        return $this->hasPropertyCache[$propertyName] = \false;
    }
    public function hasInstanceProperty(string $propertyName): bool
    {
        if (array_key_exists($propertyName, $this->hasInstancePropertyCache)) {
            return $this->hasInstancePropertyCache[$propertyName];
        }
        if ($this->isEnum()) {
            return $this->hasInstancePropertyCache[$propertyName] = $this->hasNativeProperty($propertyName);
        }
        $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
        if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            $property = $phpClassReflectionExtension->getNativeProperty($this, $propertyName);
            if (!$property->isStatic()) {
                return $this->hasInstancePropertyCache[$propertyName] = \true;
            }
        }
        if ($this->allowsDynamicProperties()) {
            foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->propertiesClassReflectionExtensions as $extension) {
                if ($extension->hasProperty($this, $propertyName)) {
                    $property = $extension->getProperty($this, $propertyName);
                    if ($property->isStatic()) {
                        continue;
                    }
                    return $this->hasInstancePropertyCache[$propertyName] = \true;
                }
            }
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension->hasInstanceProperty($this, $propertyName)) {
            return $this->hasInstancePropertyCache[$propertyName] = \true;
        }
        return $this->hasInstancePropertyCache[$propertyName] = \false;
    }
    public function hasStaticProperty(string $propertyName): bool
    {
        if (array_key_exists($propertyName, $this->hasStaticPropertyCache)) {
            return $this->hasStaticPropertyCache[$propertyName];
        }
        $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
        if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            $property = $phpClassReflectionExtension->getNativeProperty($this, $propertyName);
            if ($property->isStatic()) {
                return $this->hasStaticPropertyCache[$propertyName] = \true;
            }
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension->hasStaticProperty($this, $propertyName)) {
            return $this->hasStaticPropertyCache[$propertyName] = \true;
        }
        return $this->hasStaticPropertyCache[$propertyName] = \false;
    }
    public function hasMethod(string $methodName): bool
    {
        if (array_key_exists($methodName, $this->hasMethodCache)) {
            return $this->hasMethodCache[$methodName];
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->hasMethod($this, $methodName)) {
            return $this->hasMethodCache[$methodName] = \true;
        }
        foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->methodsClassReflectionExtensions as $extension) {
            if ($extension->hasMethod($this, $methodName)) {
                return $this->hasMethodCache[$methodName] = \true;
            }
        }
        if ($this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsMethodsClassReflectionExtension->hasMethod($this, $methodName)) {
            return $this->hasMethodCache[$methodName] = \true;
        }
        return $this->hasMethodCache[$methodName] = \false;
    }
    public function getMethod(string $methodName, \PHPStan\Reflection\ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\ExtendedMethodReflection
    {
        $key = $methodName;
        if ($scope->isInClass()) {
            $key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
        }
        if (array_key_exists($key, $this->methods)) {
            return $this->methods[$key];
        }
        $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
        if ($phpClassReflectionExtension->hasMethod($this, $methodName)) {
            $method = $phpClassReflectionExtension->getMethod($this, $methodName);
            if ($scope->canCallMethod($method)) {
                return $this->methods[$key] = $method;
            }
            $this->methods[$key] = $method;
        }
        foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->methodsClassReflectionExtensions as $extension) {
            if (!$extension->hasMethod($this, $methodName)) {
                continue;
            }
            $method = $this->wrapExtendedMethod($extension->getMethod($this, $methodName));
            if ($scope->canCallMethod($method)) {
                return $this->methods[$key] = $method;
            }
            $this->methods[$key] = $method;
        }
        if (!isset($this->methods[$key])) {
            $requireExtendsMethodsClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsMethodsClassReflectionExtension;
            if ($requireExtendsMethodsClassReflectionExtension->hasMethod($this, $methodName)) {
                $method = $requireExtendsMethodsClassReflectionExtension->getMethod($this, $methodName);
                $this->methods[$key] = $method;
            }
        }
        if (!isset($this->methods[$key])) {
            throw new \PHPStan\Reflection\MissingMethodFromReflectionException($this->getName(), $methodName);
        }
        return $this->methods[$key];
    }
    private function wrapExtendedMethod(\PHPStan\Reflection\MethodReflection $method): \PHPStan\Reflection\ExtendedMethodReflection
    {
        if ($method instanceof \PHPStan\Reflection\ExtendedMethodReflection) {
            return $method;
        }
        return new \PHPStan\Reflection\WrappedExtendedMethodReflection($method);
    }
    private function wrapExtendedProperty(string $propertyName, \PHPStan\Reflection\PropertyReflection $method): \PHPStan\Reflection\ExtendedPropertyReflection
    {
        if ($method instanceof \PHPStan\Reflection\ExtendedPropertyReflection) {
            return $method;
        }
        return new \PHPStan\Reflection\WrappedExtendedPropertyReflection($propertyName, $method);
    }
    public function hasNativeMethod(string $methodName): bool
    {
        return $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->hasNativeMethod($this, $methodName);
    }
    public function getNativeMethod(string $methodName): \PHPStan\Reflection\ExtendedMethodReflection
    {
        if (!$this->hasNativeMethod($methodName)) {
            throw new \PHPStan\Reflection\MissingMethodFromReflectionException($this->getName(), $methodName);
        }
        return $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->getNativeMethod($this, $methodName);
    }
    public function hasConstructor(): bool
    {
        return $this->findConstructor() !== null;
    }
    public function getConstructor(): \PHPStan\Reflection\ExtendedMethodReflection
    {
        $constructor = $this->findConstructor();
        if ($constructor === null) {
            throw new ShouldNotHappenException();
        }
        return $this->getNativeMethod($constructor->betterReflectionMethod->getName());
    }
    private function findConstructor(): ?ReflectionMethod
    {
        $constructor = $this->reflection->getConstructor();
        if ($constructor === null) {
            return null;
        }
        if ($this->phpVersion->versionId < 80000) {
            return $constructor;
        }
        if (strtolower($constructor->betterReflectionMethod->getName()) !== '__construct') {
            return null;
        }
        return $constructor;
    }
    /** @internal */
    public function evictPrivateSymbols(): void
    {
        foreach ($this->constants as $name => $constant) {
            if (!$constant->reflection->isPrivate()) {
                continue;
            }
            unset($this->constants[$name]);
        }
        foreach ($this->properties as $name => $property) {
            if (!$property->isPrivate()) {
                continue;
            }
            unset($this->properties[$name]);
        }
        foreach ($this->instanceProperties as $name => $property) {
            if (!$property->isPrivate()) {
                continue;
            }
            unset($this->instanceProperties[$name]);
        }
        foreach ($this->staticProperties as $name => $property) {
            if (!$property->isPrivate()) {
                continue;
            }
            unset($this->staticProperties[$name]);
        }
        foreach ($this->methods as $name => $method) {
            if (!$method->isPrivate()) {
                continue;
            }
            unset($this->methods[$name]);
        }
        // PhpClassReflectionExtension's member caches are governed by their own LRU
        // (see PhpClassReflectionExtension::touchMemberCacheKey()) instead of per-class
        // private-symbol eviction.
    }
    /** @deprecated Use getInstanceProperty or getStaticProperty */
    public function getProperty(string $propertyName, \PHPStan\Reflection\ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\ExtendedPropertyReflection
    {
        if ($this->isEnum()) {
            return $this->getNativeProperty($propertyName);
        }
        $key = $propertyName;
        if ($scope->isInClass()) {
            $key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
        }
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }
        $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
        if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            $property = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->getProperty($this, $propertyName, $scope);
            if ($scope->canReadProperty($property)) {
                return $this->properties[$key] = $property;
            }
            $this->properties[$key] = $property;
        }
        if ($this->allowsDynamicProperties()) {
            foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->propertiesClassReflectionExtensions as $extension) {
                if (!$extension->hasProperty($this, $propertyName)) {
                    continue;
                }
                $property = $this->wrapExtendedProperty($propertyName, $extension->getProperty($this, $propertyName));
                if ($scope->canReadProperty($property)) {
                    return $this->properties[$key] = $property;
                }
                $this->properties[$key] = $property;
            }
        }
        // For BC purpose
        if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            $property = $phpClassReflectionExtension->getProperty($this, $propertyName, $scope);
            return $this->properties[$key] = $property;
        }
        if (!isset($this->properties[$key])) {
            $requireExtendsPropertiesClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension;
            if ($requireExtendsPropertiesClassReflectionExtension->hasProperty($this, $propertyName)) {
                $property = $requireExtendsPropertiesClassReflectionExtension->getProperty($this, $propertyName);
                $this->properties[$key] = $property;
            }
        }
        if (!isset($this->properties[$key])) {
            throw new \PHPStan\Reflection\MissingPropertyFromReflectionException($this->getName(), $propertyName);
        }
        return $this->properties[$key];
    }
    public function getInstanceProperty(string $propertyName, \PHPStan\Reflection\ClassMemberAccessAnswerer $scope): \PHPStan\Reflection\ExtendedPropertyReflection
    {
        if ($this->isEnum()) {
            return $this->getNativeProperty($propertyName);
        }
        $key = $propertyName;
        if ($scope->isInClass()) {
            $key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
        }
        if (!isset($this->instanceProperties[$key])) {
            $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
            if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
                $property = $phpClassReflectionExtension->getProperty($this, $propertyName, $scope);
                if (!$property->isStatic()) {
                    if ($scope->canReadProperty($property)) {
                        return $this->instanceProperties[$key] = $property;
                    }
                    $this->instanceProperties[$key] = $property;
                }
            }
            if ($this->allowsDynamicProperties()) {
                foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->propertiesClassReflectionExtensions as $extension) {
                    if (!$extension->hasProperty($this, $propertyName)) {
                        continue;
                    }
                    $nakedProperty = $extension->getProperty($this, $propertyName);
                    if ($nakedProperty->isStatic()) {
                        continue;
                    }
                    $property = $this->wrapExtendedProperty($propertyName, $nakedProperty);
                    if ($scope->canReadProperty($property)) {
                        return $this->instanceProperties[$key] = $property;
                    }
                    $this->instanceProperties[$key] = $property;
                }
            }
        }
        if (!isset($this->instanceProperties[$key])) {
            $requireExtendsPropertiesClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension;
            if ($requireExtendsPropertiesClassReflectionExtension->hasInstanceProperty($this, $propertyName)) {
                $property = $requireExtendsPropertiesClassReflectionExtension->getInstanceProperty($this, $propertyName);
                $this->instanceProperties[$key] = $property;
            }
        }
        if (!isset($this->instanceProperties[$key])) {
            throw new \PHPStan\Reflection\MissingPropertyFromReflectionException($this->getName(), $propertyName);
        }
        return $this->instanceProperties[$key];
    }
    public function getStaticProperty(string $propertyName): \PHPStan\Reflection\ExtendedPropertyReflection
    {
        $key = $propertyName;
        if (isset($this->staticProperties[$key])) {
            return $this->staticProperties[$key];
        }
        $phpClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension;
        if ($phpClassReflectionExtension->hasProperty($this, $propertyName)) {
            $nakedProperty = $phpClassReflectionExtension->getProperty($this, $propertyName, new OutOfClassScope());
            if ($nakedProperty->isStatic()) {
                $property = $this->wrapExtendedProperty($propertyName, $nakedProperty);
                if ($property->isStatic()) {
                    return $this->staticProperties[$key] = $property;
                }
            }
        }
        $requireExtendsPropertiesClassReflectionExtension = $this->classReflectionExtensionRegistryProvider->getRegistry()->requireExtendsPropertiesClassReflectionExtension;
        if ($requireExtendsPropertiesClassReflectionExtension->hasStaticProperty($this, $propertyName)) {
            $property = $requireExtendsPropertiesClassReflectionExtension->getStaticProperty($this, $propertyName);
            return $this->staticProperties[$key] = $property;
        }
        throw new \PHPStan\Reflection\MissingPropertyFromReflectionException($this->getName(), $propertyName);
    }
    public function hasNativeProperty(string $propertyName): bool
    {
        return $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->hasProperty($this, $propertyName);
    }
    public function getNativeProperty(string $propertyName): PhpPropertyReflection
    {
        if (!$this->hasNativeProperty($propertyName)) {
            throw new \PHPStan\Reflection\MissingPropertyFromReflectionException($this->getName(), $propertyName);
        }
        return $this->classReflectionExtensionRegistryProvider->getRegistry()->phpClassReflectionExtension->getNativeProperty($this, $propertyName);
    }
    public function isAbstract(): bool
    {
        return $this->reflection->isAbstract();
    }
    public function isInterface(): bool
    {
        return $this->reflection->isInterface();
    }
    public function isTrait(): bool
    {
        return $this->reflection->isTrait();
    }
    /**
     * @phpstan-assert-if-true ReflectionEnum $this->reflection
     * @phpstan-assert-if-true ReflectionEnum $this->getNativeReflection()
     */
    public function isEnum(): bool
    {
        return $this->reflection instanceof ReflectionEnum && $this->reflection->betterReflectionEnum->isEnum();
    }
    /**
     * @return 'Interface'|'Trait'|'Enum'|'Class'
     */
    public function getClassTypeDescription(): string
    {
        if ($this->isInterface()) {
            return 'Interface';
        } elseif ($this->isTrait()) {
            return 'Trait';
        } elseif ($this->isEnum()) {
            return 'Enum';
        }
        return 'Class';
    }
    public function isReadOnly(): bool
    {
        return $this->reflection->isReadOnly();
    }
    public function isBackedEnum(): bool
    {
        if (!$this->reflection instanceof ReflectionEnum) {
            return \false;
        }
        return $this->reflection->betterReflectionEnum->isBacked();
    }
    public function getBackedEnumType(): ?Type
    {
        if (!$this->reflection instanceof ReflectionEnum) {
            return null;
        }
        if (!$this->reflection->betterReflectionEnum->isBacked()) {
            return null;
        }
        return TypehintHelper::decideTypeFromReflection($this->reflection->getBackingType());
    }
    public function hasEnumCase(string $name): bool
    {
        if (!$this->isEnum()) {
            return \false;
        }
        return $this->reflection->hasCase($name);
    }
    /**
     * @return array<string, EnumCaseReflection>
     */
    public function getEnumCases(): array
    {
        if (!$this->isEnum()) {
            throw new ShouldNotHappenException();
        }
        if ($this->enumCases !== null) {
            return $this->enumCases;
        }
        $cases = [];
        $initializerExprContext = \PHPStan\Reflection\InitializerExprContext::fromClassReflection($this);
        foreach ($this->reflection->cases->get() as $case) {
            $valueType = null;
            if ($case instanceof ReflectionEnumBackedCase && $case->betterReflectionEnumCase->hasValueExpression()) {
                $valueType = $this->initializerExprTypeResolver->getType($case->betterReflectionEnumCase->getValueExpression(), $initializerExprContext);
            }
            $caseName = $case->getName();
            $attributes = $this->attributeReflectionFactory->fromNativeReflection($case->getAttributes(), \PHPStan\Reflection\InitializerExprContext::fromClass($this->getName(), $this->getFileName()));
            $cases[$caseName] = new \PHPStan\Reflection\EnumCaseReflection($this, $case, $valueType, $attributes, $this->deprecationProvider);
        }
        return $this->enumCases = $cases;
    }
    public function getEnumCase(string $name): \PHPStan\Reflection\EnumCaseReflection
    {
        if (!$this->hasEnumCase($name)) {
            throw new ShouldNotHappenException(sprintf('Enum case %s::%s does not exist.', $this->getDisplayName(), $name));
        }
        if (!$this->reflection instanceof ReflectionEnum) {
            throw new ShouldNotHappenException();
        }
        if ($this->enumCases !== null && array_key_exists($name, $this->enumCases)) {
            return $this->enumCases[$name];
        }
        $case = $this->reflection->getCase($name);
        $valueType = null;
        if ($case instanceof ReflectionEnumBackedCase && $case->betterReflectionEnumCase->hasValueExpression()) {
            $valueType = $this->initializerExprTypeResolver->getType($case->betterReflectionEnumCase->getValueExpression(), \PHPStan\Reflection\InitializerExprContext::fromClassReflection($this));
        }
        $attributes = $this->attributeReflectionFactory->fromNativeReflection($case->getAttributes(), \PHPStan\Reflection\InitializerExprContext::fromClass($this->getName(), $this->getFileName()));
        return new \PHPStan\Reflection\EnumCaseReflection($this, $case, $valueType, $attributes, $this->deprecationProvider);
    }
    public function isClass(): bool
    {
        return !$this->isInterface() && !$this->isTrait() && !$this->isEnum();
    }
    public function isAnonymous(): bool
    {
        return $this->anonymousFilename !== null;
    }
    public function is(string $className): bool
    {
        if ($this->getName() === $className) {
            return \true;
        }
        if (!$this->reflectionProvider->hasClass($className)) {
            return \false;
        }
        return $this->isSubclassOfClass($this->reflectionProvider->getClass($className));
    }
    /**
     * @deprecated Use isSubclassOfClass instead.
     */
    public function isSubclassOf(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return \false;
        }
        return $this->isSubclassOfClass($this->reflectionProvider->getClass($className));
    }
    public function isSubclassOfClass(self $class): bool
    {
        $cacheKey = $class->getCacheKey();
        if (isset($this->subclasses[$cacheKey])) {
            return $this->subclasses[$cacheKey];
        }
        if ($class->isFinalByKeyword() || $class->isAnonymous()) {
            return $this->subclasses[$cacheKey] = \false;
        }
        try {
            return $this->subclasses[$cacheKey] = $this->reflection->isSubclassOf($class->getName());
        } catch (ReflectionException $e) {
            return $this->subclasses[$cacheKey] = \false;
        }
    }
    public function implementsInterface(string $className): bool
    {
        try {
            return $this->reflection->implementsInterface($className);
        } catch (ReflectionException $e) {
            return \false;
        }
    }
    /**
     * @return list<ClassReflection>
     */
    public function getParents(): array
    {
        $parents = [];
        $parent = $this->getParentClass();
        while ($parent !== null) {
            $parents[] = $parent;
            $parent = $parent->getParentClass();
        }
        return $parents;
    }
    /**
     * @return array<string, ClassReflection>
     */
    public function getInterfaces(): array
    {
        if ($this->cachedInterfaces !== null) {
            return $this->cachedInterfaces;
        }
        $interfaces = $this->getImmediateInterfaces();
        $immediateInterfaces = $interfaces;
        $parent = $this->getParentClass();
        while ($parent !== null) {
            foreach ($parent->getImmediateInterfaces() as $parentInterface) {
                $interfaces[$parentInterface->getName()] = $parentInterface;
                foreach ($this->collectInterfaces($parentInterface) as $parentInterfaceInterface) {
                    $interfaces[$parentInterfaceInterface->getName()] = $parentInterfaceInterface;
                }
            }
            $parent = $parent->getParentClass();
        }
        foreach ($immediateInterfaces as $immediateInterface) {
            foreach ($this->collectInterfaces($immediateInterface) as $interfaceInterface) {
                $interfaces[$interfaceInterface->getName()] = $interfaceInterface;
            }
        }
        $this->cachedInterfaces = $interfaces;
        return $interfaces;
    }
    /**
     * @return array<string, ClassReflection>
     */
    private function collectInterfaces(\PHPStan\Reflection\ClassReflection $interface): array
    {
        $interfaces = [];
        $queue = [$interface];
        while ($queue !== []) {
            $current = array_pop($queue);
            foreach ($current->getImmediateInterfaces() as $immediateInterface) {
                $name = $immediateInterface->getName();
                if (array_key_exists($name, $interfaces)) {
                    continue;
                }
                $interfaces[$name] = $immediateInterface;
                $queue[] = $immediateInterface;
            }
        }
        return $interfaces;
    }
    /**
     * @return array<string, ClassReflection>
     */
    public function getImmediateInterfaces(): array
    {
        $indirectInterfaceNames = [];
        $parent = $this->getParentClass();
        while ($parent !== null) {
            foreach ($parent->getNativeReflection()->getInterfaceNames() as $parentInterfaceName) {
                $indirectInterfaceNames[] = $parentInterfaceName;
            }
            $parent = $parent->getParentClass();
        }
        foreach ($this->getNativeReflection()->getInterfaces() as $interfaceInterface) {
            foreach ($interfaceInterface->betterReflectionClass->getInterfaceNames() as $interfaceInterfaceName) {
                $indirectInterfaceNames[] = $interfaceInterfaceName;
            }
        }
        if ($this->reflection->isInterface()) {
            $implementsTags = $this->getExtendsTags();
        } else {
            $implementsTags = $this->getImplementsTags();
        }
        $immediateInterfaceNames = array_diff($this->getNativeReflection()->getInterfaceNames(), $indirectInterfaceNames);
        $immediateInterfaces = [];
        foreach ($immediateInterfaceNames as $immediateInterfaceName) {
            if (!$this->reflectionProvider->hasClass($immediateInterfaceName)) {
                continue;
            }
            $immediateInterface = $this->reflectionProvider->getClass($immediateInterfaceName);
            if (array_key_exists($immediateInterface->getName(), $implementsTags)) {
                $implementsTag = $implementsTags[$immediateInterface->getName()];
                $implementedType = $implementsTag->getType();
                if ($this->isGeneric()) {
                    $implementedType = TemplateTypeHelper::resolveTemplateTypes($implementedType, $this->getActiveTemplateTypeMapForAncestorResolution(), $this->getCallSiteVarianceMap(), TemplateTypeVariance::createStatic(), \true);
                }
                if ($implementedType instanceof GenericObjectType && $implementedType->getClassReflection() !== null) {
                    $immediateInterfaces[$immediateInterface->getName()] = $implementedType->getClassReflection();
                    continue;
                }
            }
            if ($immediateInterface->isGeneric()) {
                $immediateInterfaces[$immediateInterface->getName()] = $immediateInterface->withTypes(array_values($immediateInterface->getTemplateTypeMap()->map(static fn(): Type => new ErrorType())->getTypes()));
                continue;
            }
            $immediateInterfaces[$immediateInterface->getName()] = $immediateInterface;
        }
        return $immediateInterfaces;
    }
    /**
     * @return array<string, ClassReflection>
     */
    public function getTraits(bool $recursive = \false): array
    {
        $traits = [];
        if ($recursive) {
            foreach ($this->collectTraits($this->getNativeReflection()) as $trait) {
                $traits[$trait->betterReflectionClass->getName()] = $trait;
            }
        } else {
            $traits = $this->getNativeReflection()->getTraits();
        }
        $traits = array_map(fn(ReflectionClass $trait): \PHPStan\Reflection\ClassReflection => $this->reflectionProvider->getClass($trait->betterReflectionClass->getName()), $traits);
        if ($recursive) {
            $parentClass = $this->getParentClass();
            if ($parentClass !== null) {
                return array_merge($traits, $parentClass->getTraits(\true));
            }
        }
        return $traits;
    }
    /**
     * @return list<class-string>
     */
    public function getParentClassesNames(): array
    {
        $parentNames = [];
        $parentClass = $this->getParentClass();
        while ($parentClass !== null) {
            $parentNames[] = $parentClass->getName();
            $parentClass = $parentClass->getParentClass();
        }
        return $parentNames;
    }
    public function hasConstant(string $name): bool
    {
        if (!$this->getNativeReflection()->hasConstant($name)) {
            return \false;
        }
        $reflectionConstant = $this->getNativeReflection()->getReflectionConstant($name);
        if ($reflectionConstant === \false) {
            return \false;
        }
        return $this->reflectionProvider->hasClass($reflectionConstant->getDeclaringClass()->betterReflectionClass->getName());
    }
    public function getConstant(string $name): \PHPStan\Reflection\ClassConstantReflection
    {
        if (!isset($this->constants[$name])) {
            $reflectionConstant = $this->getNativeReflection()->getReflectionConstant($name);
            if ($reflectionConstant === \false) {
                throw new \PHPStan\Reflection\MissingConstantFromReflectionException($this->getName(), $name);
            }
            $deprecation = $this->deprecationProvider->getClassConstantDeprecation($reflectionConstant);
            $deprecatedDescription = $deprecation === null ? null : $deprecation->description;
            $isDeprecated = $deprecation !== null;
            $declaringClass = $this->getAncestorWithClassName($reflectionConstant->getDeclaringClass()->betterReflectionClass->getName());
            if ($declaringClass === null) {
                throw new ShouldNotHappenException();
            }
            $fileName = $declaringClass->getFileName();
            $phpDocType = null;
            $currentResolvedPhpDoc = $this->findConstantResolvedPhpDoc($reflectionConstant);
            $nativeType = null;
            if ($reflectionConstant->getType() !== null) {
                $nativeType = TypehintHelper::decideTypeFromReflection($reflectionConstant->getType(), \null, $declaringClass);
            } elseif ($this->signatureMapProvider->hasClassConstantMetadata($declaringClass->getName(), $name)) {
                $nativeType = $this->signatureMapProvider->getClassConstantMetadata($declaringClass->getName(), $name)['nativeType'];
            }
            $resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForConstant($declaringClass, $name, $currentResolvedPhpDoc);
            $isInternal = \false;
            $isFinal = \false;
            if ($resolvedPhpDoc !== null) {
                if (!$isDeprecated) {
                    $deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->message : null;
                    $isDeprecated = $resolvedPhpDoc->isDeprecated();
                }
                $isInternal = $resolvedPhpDoc->isInternal();
                $isFinal = $resolvedPhpDoc->isFinal();
                $phpDocType = self::resolveConstantVarPhpDocType($resolvedPhpDoc, $nativeType, $declaringClass);
            }
            $this->constants[$name] = new \PHPStan\Reflection\RealClassClassConstantReflection($this->initializerExprTypeResolver, $declaringClass, $reflectionConstant, $nativeType, $phpDocType, $resolvedPhpDoc, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $this->attributeReflectionFactory->fromNativeReflection($reflectionConstant->getAttributes(), \PHPStan\Reflection\InitializerExprContext::fromClass($declaringClass->getName(), $fileName)));
        }
        return $this->constants[$name];
    }
    /**
     * Returns the @var PHPDoc type of a class constant, if any.
     * Does not walk ancestors, so it's safe to call from class-constant
     * type resolution (which re-enters via @extends<value-of<...>>).
     *
     * @internal
     */
    public function getConstantPhpDocType(string $name): ?Type
    {
        $reflectionConstant = $this->getNativeReflection()->getReflectionConstant($name);
        if ($reflectionConstant === \false) {
            return null;
        }
        $resolvedPhpDoc = $this->findConstantResolvedPhpDoc($reflectionConstant);
        if ($resolvedPhpDoc === null) {
            return null;
        }
        $nativeType = null;
        if ($reflectionConstant->getType() !== null) {
            $nativeType = TypehintHelper::decideTypeFromReflection($reflectionConstant->getType());
        }
        $declaringClassName = $reflectionConstant->getDeclaringClass()->betterReflectionClass->getName();
        if ($declaringClassName === $this->getName()) {
            $declaringClass = $this;
        } else {
            $declaringClass = $this->getAncestorWithClassName($declaringClassName);
            if ($declaringClass === null) {
                return null;
            }
        }
        return self::resolveConstantVarPhpDocType($resolvedPhpDoc, $nativeType, $declaringClass);
    }
    private function findConstantResolvedPhpDoc(ReflectionClassConstant $reflectionConstant): ?ResolvedPhpDocBlock
    {
        $declaringClassName = $reflectionConstant->getDeclaringClass()->betterReflectionClass->getName();
        $resolvedPhpDoc = $this->stubPhpDocProvider->findClassConstantPhpDoc($declaringClassName, $reflectionConstant->betterClassConstantOrEnumCase->getName());
        if ($resolvedPhpDoc !== null) {
            return $resolvedPhpDoc;
        }
        $docComment = $reflectionConstant->betterClassConstantOrEnumCase->getDocComment() ?? \false;
        if ($docComment === \false) {
            return null;
        }
        return $this->fileTypeMapper->getResolvedPhpDoc($reflectionConstant->getDeclaringClass()->getFileName() ?: null, $declaringClassName, null, null, $docComment);
    }
    private static function resolveConstantVarPhpDocType(ResolvedPhpDocBlock $resolvedPhpDoc, ?Type $nativeType, self $declaringClass): ?Type
    {
        $varTags = $resolvedPhpDoc->getVarTags();
        if (!isset($varTags[0]) || count($varTags) !== 1) {
            return null;
        }
        $varTag = $varTags[0];
        if (!$varTag->isExplicit && $nativeType !== null && !$nativeType->isSuperTypeOf($varTag->type)->yes()) {
            return null;
        }
        return TemplateTypeHelper::resolveTemplateTypes($varTag->type, $declaringClass->getActiveTemplateTypeMap(), $declaringClass->getCallSiteVarianceMap(), TemplateTypeVariance::createInvariant());
    }
    public function hasTraitUse(string $traitName): bool
    {
        return in_array($traitName, $this->getTraitNames(), \true);
    }
    /**
     * @return list<string>
     */
    private function getTraitNames(): array
    {
        $class = $this->reflection;
        $traitNames = array_map(static fn(ReflectionClass $class) => $class->betterReflectionClass->getName(), $this->collectTraits($class));
        while ($class->getParentClass() !== \false) {
            $traitNames = array_values(array_unique(array_merge($traitNames, $class->getParentClass()->betterReflectionClass->getTraitNames())));
            $class = $class->getParentClass();
        }
        return $traitNames;
    }
    /**
     * @return array<string, TypeAlias>
     */
    public function getTypeAliases(): array
    {
        if ($this->typeAliases === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            if ($resolvedPhpDoc === null) {
                return $this->typeAliases = [];
            }
            $typeAliasImportTags = $resolvedPhpDoc->getTypeAliasImportTags();
            $typeAliasTags = $resolvedPhpDoc->getTypeAliasTags();
            $localAliases = array_map(static fn(TypeAliasTag $typeAliasTag): TypeAlias => new \PHPStan\Type\TypeAlias($typeAliasTag->typeNode, $typeAliasTag->nameScope), $typeAliasTags);
            // prevent circular imports
            if (array_key_exists($this->getName(), self::$resolvingTypeAliasImports)) {
                if ($localAliases !== []) {
                    return $this->typeAliases = $localAliases;
                }
                throw new CircularTypeAliasDefinitionException();
            }
            self::$resolvingTypeAliasImports[$this->getName()] = \true;
            $importedAliases = array_map(function (TypeAliasImportTag $typeAliasImportTag): ?TypeAlias {
                $importedAlias = $typeAliasImportTag->importedAlias;
                $importedFromClassName = $typeAliasImportTag->importedFrom;
                if (!$this->reflectionProvider->hasClass($importedFromClassName)) {
                    return null;
                }
                $importedFromReflection = $this->reflectionProvider->getClass($importedFromClassName);
                try {
                    $typeAliases = $importedFromReflection->getTypeAliases();
                } catch (CircularTypeAliasDefinitionException $e) {
                    return TypeAlias::invalid();
                }
                if (!array_key_exists($importedAlias, $typeAliases)) {
                    return null;
                }
                return $typeAliases[$importedAlias];
            }, $typeAliasImportTags);
            unset(self::$resolvingTypeAliasImports[$this->getName()]);
            $this->typeAliases = array_filter(array_merge($importedAliases, $localAliases), static fn(?TypeAlias $typeAlias): bool => $typeAlias !== null);
        }
        return $this->typeAliases;
    }
    public function getDeprecatedDescription(): ?string
    {
        if ($this->isDeprecated === null) {
            $this->resolveDeprecation();
        }
        return $this->deprecatedDescription;
    }
    public function isDeprecated(): bool
    {
        if ($this->isDeprecated === null) {
            $this->resolveDeprecation();
        }
        return $this->isDeprecated;
    }
    /**
     * @phpstan-assert bool $this->isDeprecated
     */
    private function resolveDeprecation(): void
    {
        $deprecation = $this->deprecationProvider->getClassDeprecation($this->reflection);
        if ($deprecation !== null) {
            $this->isDeprecated = \true;
            $this->deprecatedDescription = $deprecation->description;
            return;
        }
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc !== null && $resolvedPhpDoc->isDeprecated()) {
            $this->isDeprecated = \true;
            $this->deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->message : null;
            return;
        }
        if ($this->isTrait() && count($this->getNativeReflection()->getAttributes('Deprecated')) > 0) {
            $this->isDeprecated = \true;
            $this->deprecatedDescription = null;
            return;
        }
        $this->isDeprecated = \false;
        $this->deprecatedDescription = null;
    }
    public function isBuiltin(): bool
    {
        return $this->reflection->isInternal();
    }
    public function isInternal(): bool
    {
        if ($this->isInternal === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            $this->isInternal = $resolvedPhpDoc !== null && $resolvedPhpDoc->isInternal();
        }
        return $this->isInternal;
    }
    public function isFinal(): bool
    {
        if ($this->isFinalByKeyword()) {
            return \true;
        }
        if ($this->isFinal === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            $this->isFinal = $resolvedPhpDoc !== null && $resolvedPhpDoc->isFinal();
        }
        return $this->isFinal;
    }
    public function isImmutable(): bool
    {
        if ($this->isImmutable === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            $this->isImmutable = $resolvedPhpDoc !== null && ($resolvedPhpDoc->isImmutable() || $resolvedPhpDoc->isReadOnly());
            $parentClass = $this->getParentClass();
            if ($parentClass !== null && !$this->isImmutable) {
                $this->isImmutable = $parentClass->isImmutable();
            }
        }
        return $this->isImmutable;
    }
    public function hasConsistentConstructor(): bool
    {
        if ($this->hasConsistentConstructor === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            $this->hasConsistentConstructor = $resolvedPhpDoc !== null && $resolvedPhpDoc->hasConsistentConstructor();
        }
        return $this->hasConsistentConstructor;
    }
    public function acceptsNamedArguments(): bool
    {
        if ($this->acceptsNamedArguments === null) {
            $resolvedPhpDoc = $this->getResolvedPhpDoc();
            $this->acceptsNamedArguments = $resolvedPhpDoc === null || $resolvedPhpDoc->acceptsNamedArguments();
        }
        return $this->acceptsNamedArguments;
    }
    public function hasFinalByKeywordOverride(): bool
    {
        return $this->finalByKeywordOverride !== null;
    }
    public function isFinalByKeyword(): bool
    {
        if ($this->isAnonymous()) {
            return \true;
        }
        if ($this->finalByKeywordOverride !== null) {
            return $this->finalByKeywordOverride;
        }
        return $this->reflection->isFinal();
    }
    public function isAttributeClass(): bool
    {
        return $this->findAttributeFlags() !== null;
    }
    private function findAttributeFlags(): ?int
    {
        if ($this->isInterface() || $this->isTrait() || $this->isEnum()) {
            return null;
        }
        $nativeAttributes = $this->reflection->getAttributes(Attribute::class);
        if (count($nativeAttributes) === 1) {
            if (!$this->reflectionProvider->hasClass(Attribute::class)) {
                return null;
            }
            $attributeClass = $this->reflectionProvider->getClass(Attribute::class);
            $arguments = [];
            foreach ($nativeAttributes[0]->betterReflectionAttribute->getArgumentsExpressions() as $i => $expression) {
                if ($i === '') {
                    throw new ShouldNotHappenException();
                }
                $arguments[] = new Arg($expression, \false, \false, [], is_int($i) ? null : new Identifier($i));
            }
            if (!$attributeClass->hasConstructor()) {
                return null;
            }
            $attributeConstructor = $attributeClass->getConstructor();
            $attributeConstructorVariant = $attributeConstructor->getOnlyVariant();
            if (count($arguments) === 0) {
                $flagType = $attributeConstructorVariant->getParameters()[0]->getDefaultValue();
            } else {
                $staticCall = ArgumentsNormalizer::reorderStaticCallArguments($attributeConstructorVariant, new StaticCall(new FullyQualified(Attribute::class), $attributeConstructor->getName(), $arguments));
                if ($staticCall === null) {
                    return null;
                }
                $flagExpr = $staticCall->getArgs()[0]->value;
                $flagType = $this->initializerExprTypeResolver->getType($flagExpr, \PHPStan\Reflection\InitializerExprContext::fromClassReflection($this));
            }
            if (!$flagType instanceof ConstantIntegerType) {
                return null;
            }
            return $flagType->getValue();
        }
        return null;
    }
    /**
     * @return list<AttributeReflection>
     */
    public function getAttributes(): array
    {
        return $this->attributeReflectionFactory->fromNativeReflection($this->reflection->getAttributes(), \PHPStan\Reflection\InitializerExprContext::fromClass($this->getName(), $this->getFileName()));
    }
    public function getAttributeClassFlags(): int
    {
        $flags = $this->findAttributeFlags();
        if ($flags === null) {
            throw new ShouldNotHappenException();
        }
        return $flags;
    }
    public function getObjectType(): ObjectType
    {
        if (!$this->isGeneric()) {
            return new ObjectType($this->getName());
        }
        return new GenericObjectType($this->getName(), $this->typeMapToList($this->getActiveTemplateTypeMap()), \null, \null, $this->varianceMapToList($this->getCallSiteVarianceMap()));
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        if ($this->templateTypeMap !== null) {
            return $this->templateTypeMap;
        }
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            $this->templateTypeMap = TemplateTypeMap::createEmpty();
            return $this->templateTypeMap;
        }
        $templateTypeScope = TemplateTypeScope::createWithClass($this->getName());
        $templateTypeMap = new TemplateTypeMap(array_map(static fn(TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $this->getTemplateTags()));
        $this->templateTypeMap = $templateTypeMap;
        return $templateTypeMap;
    }
    public function getActiveTemplateTypeMap(): TemplateTypeMap
    {
        if ($this->activeTemplateTypeMap !== null) {
            return $this->activeTemplateTypeMap;
        }
        $resolved = $this->resolvedTemplateTypeMap;
        if ($resolved !== null) {
            $templateTypeMap = $this->getTemplateTypeMap();
            return $this->activeTemplateTypeMap = $resolved->map(static function (string $name, Type $type) use ($templateTypeMap): Type {
                if ($type instanceof ErrorType) {
                    $templateType = $templateTypeMap->getType($name);
                    if ($templateType !== null) {
                        return TemplateTypeHelper::resolveToDefaults($templateType);
                    }
                }
                return $type;
            });
        }
        return $this->activeTemplateTypeMap = $this->getTemplateTypeMap();
    }
    public function getPossiblyIncompleteActiveTemplateTypeMap(): TemplateTypeMap
    {
        return $this->resolvedTemplateTypeMap ?? $this->getTemplateTypeMap();
    }
    /**
     * Returns a template type map for resolving ancestor type declarations (@extends, @implements).
     * Like getPossiblyIncompleteActiveTemplateTypeMap(), but resolves ErrorType entries
     * to their template bounds when the bound is not mixed. This ensures that when a child
     * class narrows a template bound (e.g. `@template T of SpecificType`), the narrowed bound
     * is propagated to ancestor declarations instead of being lost as ErrorType.
     */
    private function getActiveTemplateTypeMapForAncestorResolution(): TemplateTypeMap
    {
        $map = $this->getPossiblyIncompleteActiveTemplateTypeMap();
        $templateTypeMap = $this->getTemplateTypeMap();
        return $map->map(static function (string $name, Type $type) use ($templateTypeMap): Type {
            if (!$type instanceof ErrorType) {
                return $type;
            }
            $templateType = $templateTypeMap->getType($name);
            if (!$templateType instanceof TemplateType) {
                return $type;
            }
            $bound = $templateType->getBound();
            if ($bound instanceof MixedType) {
                return $type;
            }
            return TemplateTypeHelper::resolveToDefaults($templateType);
        });
    }
    private function getDefaultCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        if ($this->defaultCallSiteVarianceMap !== null) {
            return $this->defaultCallSiteVarianceMap;
        }
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            $this->defaultCallSiteVarianceMap = TemplateTypeVarianceMap::createEmpty();
            return $this->defaultCallSiteVarianceMap;
        }
        $map = [];
        foreach ($this->getTemplateTags() as $templateTag) {
            $map[$templateTag->name] = TemplateTypeVariance::createInvariant();
        }
        $this->defaultCallSiteVarianceMap = new TemplateTypeVarianceMap($map);
        return $this->defaultCallSiteVarianceMap;
    }
    public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        return $this->callSiteVarianceMap ??= $this->resolvedCallSiteVarianceMap ?? $this->getDefaultCallSiteVarianceMap();
    }
    public function isGeneric(): bool
    {
        if ($this->isGeneric === null) {
            if ($this->isEnum()) {
                return $this->isGeneric = \false;
            }
            $this->isGeneric = count($this->getTemplateTags()) > 0;
        }
        return $this->isGeneric;
    }
    /**
     * @param array<int, Type> $types
     */
    public function typeMapFromList(array $types): TemplateTypeMap
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return TemplateTypeMap::createEmpty();
        }
        $map = [];
        $i = 0;
        $className = $this->getName();
        foreach ($resolvedPhpDoc->templateTags as $tag) {
            $type = $types[$i] ?? $tag->default ?? $tag->bound;
            // A template default may reference other template types of the same
            // class, possibly nested inside a composite type like `TValue|null`.
            // Never descend into a TemplateType's bound - bounds may be
            // self-referential (`@template T of Foo<T>`).
            $type = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($map, $className): Type {
                if (!$type instanceof TemplateType) {
                    return $traverse($type);
                }
                if ($type->getScope()->getClassName() !== $className) {
                    return $type;
                }
                $resolved = $map[$type->getName()] ?? null;
                if ($resolved !== null && !$resolved instanceof TemplateType) {
                    return $resolved;
                }
                return $type;
            });
            $map[$tag->name] = $type;
            $i++;
        }
        return new TemplateTypeMap($map);
    }
    /**
     * @param array<int, TemplateTypeVariance> $variances
     */
    public function varianceMapFromList(array $variances): TemplateTypeVarianceMap
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return new TemplateTypeVarianceMap([]);
        }
        $map = [];
        $i = 0;
        foreach ($resolvedPhpDoc->templateTags as $tag) {
            $map[$tag->name] = $variances[$i] ?? TemplateTypeVariance::createInvariant();
            $i++;
        }
        return new TemplateTypeVarianceMap($map);
    }
    /** @return list<Type> */
    public function typeMapToList(TemplateTypeMap $typeMap): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        $list = [];
        foreach ($resolvedPhpDoc->templateTags as $tag) {
            $list[] = $typeMap->getType($tag->name) ?? $tag->default ?? $tag->bound;
        }
        return $list;
    }
    /** @return list<TemplateTypeVariance> */
    public function varianceMapToList(TemplateTypeVarianceMap $varianceMap): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        $list = [];
        foreach ($resolvedPhpDoc->templateTags as $tag) {
            $list[] = $varianceMap->getVariance($tag->name) ?? TemplateTypeVariance::createInvariant();
        }
        return $list;
    }
    /**
     * @param array<int, Type> $types
     */
    public function withTypes(array $types): self
    {
        return $this->classReflectionFactory->create($this->displayName, $this->reflection, $this->anonymousFilename, $this->typeMapFromList($types), $this->stubPhpDocBlockCallback, null, $this->resolvedCallSiteVarianceMap, $this->finalByKeywordOverride);
    }
    /**
     * @param array<int, TemplateTypeVariance> $variances
     */
    public function withVariances(array $variances): self
    {
        return $this->classReflectionFactory->create($this->displayName, $this->reflection, $this->anonymousFilename, $this->resolvedTemplateTypeMap, $this->stubPhpDocBlockCallback, null, $this->varianceMapFromList($variances), $this->finalByKeywordOverride);
    }
    public function asFinal(): self
    {
        if ($this->getNativeReflection()->isFinal()) {
            return $this;
        }
        if ($this->finalByKeywordOverride === \true) {
            return $this;
        }
        if (!$this->isClass()) {
            return $this;
        }
        if ($this->isAbstract()) {
            return $this;
        }
        return $this->classReflectionFactory->create($this->displayName, $this->reflection, $this->anonymousFilename, $this->resolvedTemplateTypeMap, $this->stubPhpDocBlockCallback, null, $this->resolvedCallSiteVarianceMap, \true);
    }
    /**
     * Back to the finality the class declares. Unlike removeFinalKeywordOverride(),
     * which asserts non-finality, this drops the override altogether - a type holding
     * such a reflection stops claiming to be exactly this class.
     */
    public function withoutFinalByKeywordOverride(): self
    {
        if ($this->finalByKeywordOverride === null) {
            return $this;
        }
        return $this->classReflectionFactory->create($this->displayName, $this->reflection, $this->anonymousFilename, $this->resolvedTemplateTypeMap, $this->stubPhpDocBlockCallback, null, $this->resolvedCallSiteVarianceMap, null);
    }
    public function removeFinalKeywordOverride(): self
    {
        if ($this->getNativeReflection()->isFinal()) {
            return $this;
        }
        if ($this->finalByKeywordOverride === \false) {
            return $this;
        }
        if (!$this->isClass()) {
            return $this;
        }
        if ($this->isAbstract()) {
            return $this;
        }
        return $this->classReflectionFactory->create($this->displayName, $this->reflection, $this->anonymousFilename, $this->resolvedTemplateTypeMap, $this->stubPhpDocBlockCallback, null, $this->resolvedCallSiteVarianceMap, \false);
    }
    public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
    {
        if ($this->stubPhpDocBlockCallback !== null) {
            if ($this->stubPhpDocBlock === \false) {
                $this->stubPhpDocBlock = ($this->stubPhpDocBlockCallback)();
            }
            if ($this->stubPhpDocBlock !== null) {
                return $this->stubPhpDocBlock;
            }
        }
        $fileName = $this->getFileName();
        if (is_bool($this->reflectionDocComment)) {
            $docComment = $this->reflection->getDocComment();
            $this->reflectionDocComment = $docComment !== \false ? $docComment : null;
        }
        if ($this->reflectionDocComment === null) {
            return null;
        }
        if ($this->resolvedPhpDocBlock !== \false) {
            return $this->resolvedPhpDocBlock;
        }
        return $this->resolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc($fileName, $this->getName(), null, null, $this->reflectionDocComment);
    }
    public function getTraitContextResolvedPhpDoc(self $implementingClass): ?ResolvedPhpDocBlock
    {
        if (!$this->isTrait()) {
            throw new ShouldNotHappenException();
        }
        if ($implementingClass->isTrait()) {
            throw new ShouldNotHappenException();
        }
        $fileName = $this->getFileName();
        if (is_bool($this->reflectionDocComment)) {
            $docComment = $this->reflection->getDocComment();
            $this->reflectionDocComment = $docComment !== \false ? $docComment : null;
        }
        if ($this->reflectionDocComment === null) {
            return null;
        }
        if ($this->traitContextResolvedPhpDocBlock !== \false) {
            return $this->traitContextResolvedPhpDocBlock;
        }
        return $this->traitContextResolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc($fileName, $implementingClass->getName(), $this->getName(), null, $this->reflectionDocComment);
    }
    private function getFirstExtendsTag(): ?ExtendsTag
    {
        foreach ($this->getExtendsTags() as $tag) {
            return $tag;
        }
        return null;
    }
    /** @return array<string, ExtendsTag> */
    public function getExtendsTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getExtendsTags();
    }
    /** @return array<string, ImplementsTag> */
    public function getImplementsTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getImplementsTags();
    }
    /** @return array<string,TemplateTag> */
    public function getTemplateTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->templateTags;
    }
    /**
     * @return array<string,ClassReflection>
     */
    public function getAncestors(): array
    {
        if ($this->ancestors === null) {
            $ancestors = [$this->getName() => $this];
            $this->collectAncestors($ancestors);
            // the cached array must not contain $this: that would be a reference
            // cycle, and with gc_disable() a ClassReflection the reflection provider
            // does not keep, like a withTypes() copy, would then never be freed
            unset($ancestors[$this->getName()]);
            $this->ancestors = $ancestors;
        }
        return [$this->getName() => $this] + $this->ancestors;
    }
    /**
     * Descends into the interfaces, traits and parent classes, using the collected ancestors
     * as the set of already visited classes. Traits can use each other in a cycle, which is a
     * fatal error in PHP but must not make this walk run forever.
     *
     * @param array<string, ClassReflection> $ancestors
     */
    private function collectAncestors(array &$ancestors): void
    {
        $addToAncestors = static function (\PHPStan\Reflection\ClassReflection $classReflection) use (&$ancestors): void {
            if (array_key_exists($classReflection->getName(), $ancestors)) {
                return;
            }
            $ancestors[$classReflection->getName()] = $classReflection;
            $classReflection->collectAncestors($ancestors);
        };
        foreach ($this->getInterfaces() as $interface) {
            $addToAncestors($interface);
        }
        foreach ($this->getTraits() as $trait) {
            $addToAncestors($trait);
        }
        $parent = $this->getParentClass();
        if ($parent === null) {
            return;
        }
        $addToAncestors($parent);
    }
    public function getAncestorWithClassName(string $className): ?self
    {
        return $this->getAncestors()[$className] ?? null;
    }
    /**
     * @param string[] $ancestorClasses
     */
    private function isValidAncestorType(Type $type, array $ancestorClasses): bool
    {
        if (!$type instanceof GenericObjectType) {
            return \false;
        }
        $reflection = $type->getClassReflection();
        if ($reflection === null) {
            return \false;
        }
        return in_array($reflection->getName(), $ancestorClasses, \true);
    }
    /**
     * @return array<MixinTag>
     */
    public function getMixinTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getMixinTags();
    }
    /**
     * @return array<RequireExtendsTag>
     */
    public function getRequireExtendsTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getRequireExtendsTags();
    }
    /**
     * @return array<RequireImplementsTag>
     */
    public function getRequireImplementsTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getRequireImplementsTags();
    }
    /**
     * @return array<SealedTypeTag>
     */
    public function getSealedTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getSealedTags();
    }
    /**
     * @return array<string, PropertyTag>
     */
    public function getPropertyTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getPropertyTags();
    }
    /**
     * @return array<string, MethodTag>
     */
    public function getMethodTags(): array
    {
        $resolvedPhpDoc = $this->getResolvedPhpDoc();
        if ($resolvedPhpDoc === null) {
            return [];
        }
        return $resolvedPhpDoc->getMethodTags();
    }
    /**
     * @return list<Type>
     */
    public function getResolvedMixinTypes(): array
    {
        $types = [];
        foreach ($this->getMixinTags() as $mixinTag) {
            if (!$this->isGeneric()) {
                $types[] = $mixinTag->type;
                continue;
            }
            $types[] = TemplateTypeHelper::resolveTemplateTypes($mixinTag->type, $this->getActiveTemplateTypeMap(), $this->getCallSiteVarianceMap(), TemplateTypeVariance::createStatic());
        }
        return $types;
    }
    /**
     * @return array<Type>|null
     */
    public function getAllowedSubTypes(): ?array
    {
        if ($this->allowedSubTypesResolved) {
            return $this->allowedSubTypes;
        }
        $this->allowedSubTypesResolved = \true;
        foreach ($this->classReflectionExtensionRegistryProvider->getRegistry()->allowedSubTypesClassReflectionExtensions as $allowedSubTypesClassReflectionExtension) {
            if ($allowedSubTypesClassReflectionExtension->supports($this)) {
                return $this->allowedSubTypes = $allowedSubTypesClassReflectionExtension->getAllowedSubTypes($this);
            }
        }
        return null;
    }
}
