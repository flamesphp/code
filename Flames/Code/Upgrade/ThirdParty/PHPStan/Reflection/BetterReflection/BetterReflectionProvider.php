<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection;

use Closure;
use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\ConstantNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\File\DeduplicatingFileReader;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\Parser\AnonymousClassVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ParamClosureThisTag;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ClassNameHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\Constant\RuntimeConstantReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\Php\ExitFunctionReflection;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_key_first;
use function array_map;
use function in_array;
use function sprintf;
use function strtolower;
use const PHP_VERSION_ID;
#[NonAutowiredService('betterReflectionProvider')]
#[ReferencedByTurboExtension('betterReflectionProvider')]
final class BetterReflectionProvider implements ReflectionProvider
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ClassReflectionFactory $classReflectionFactory;
    private Reflector $reflector;
    private FileTypeMapper $fileTypeMapper;
    private DeprecationProvider $deprecationProvider;
    private PhpVersion $phpVersion;
    private NativeFunctionReflectionProvider $nativeFunctionReflectionProvider;
    private SignatureMapProvider $signatureMapProvider;
    private StubPhpDocProvider $stubPhpDocProvider;
    private FunctionReflectionFactory $functionReflectionFactory;
    private RelativePathHelper $relativePathHelper;
    private AnonymousClassNameHelper $anonymousClassNameHelper;
    private FileHelper $fileHelper;
    private DeduplicatingFileReader $deduplicatingFileReader;
    private PhpStormStubsSourceStubber $phpstormStubsSourceStubber;
    private AttributeReflectionFactory $attributeReflectionFactory;
    /**
     * @var list<string>
     */
    private array $universalObjectCratesClasses;
    /** @var FunctionReflection[] */
    private array $functionReflections = [];
    /** @var array<string, string|false> */
    private array $resolvedFunctionNames = [];
    /** @var array<string, string|false> */
    private array $resolvedConstantNames = [];
    /** @var ClassReflection[] */
    private array $classReflections = [];
    /** @var ClassReflection[] */
    private static array $anonymousClasses = [];
    /** @var array<string, array<string, ConstantReflection>> */
    private array $cachedConstants = [];
    /**
     * @param list<string> $universalObjectCratesClasses
     */
    public function __construct(
        InitializerExprTypeResolver $initializerExprTypeResolver,
        ClassReflectionFactory $classReflectionFactory,
        #[AutowiredParameter('@betterReflectionReflector')]
        Reflector $reflector,
        FileTypeMapper $fileTypeMapper,
        DeprecationProvider $deprecationProvider,
        PhpVersion $phpVersion,
        NativeFunctionReflectionProvider $nativeFunctionReflectionProvider,
        SignatureMapProvider $signatureMapProvider,
        StubPhpDocProvider $stubPhpDocProvider,
        FunctionReflectionFactory $functionReflectionFactory,
        RelativePathHelper $relativePathHelper,
        AnonymousClassNameHelper $anonymousClassNameHelper,
        FileHelper $fileHelper,
        DeduplicatingFileReader $deduplicatingFileReader,
        PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
        AttributeReflectionFactory $attributeReflectionFactory,
        #[AutowiredParameter('%universalObjectCratesClasses%')]
        array $universalObjectCratesClasses
    )
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->classReflectionFactory = $classReflectionFactory;
        $this->reflector = $reflector;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->deprecationProvider = $deprecationProvider;
        $this->phpVersion = $phpVersion;
        $this->nativeFunctionReflectionProvider = $nativeFunctionReflectionProvider;
        $this->signatureMapProvider = $signatureMapProvider;
        $this->stubPhpDocProvider = $stubPhpDocProvider;
        $this->functionReflectionFactory = $functionReflectionFactory;
        $this->relativePathHelper = $relativePathHelper;
        $this->anonymousClassNameHelper = $anonymousClassNameHelper;
        $this->fileHelper = $fileHelper;
        $this->deduplicatingFileReader = $deduplicatingFileReader;
        $this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->universalObjectCratesClasses = $universalObjectCratesClasses;
    }
    public function hasClass(string $className): bool
    {
        if (isset(self::$anonymousClasses[$className])) {
            return \true;
        }
        if (!ClassNameHelper::isValidClassName($className)) {
            return \false;
        }
        try {
            $this->reflector->reflectClass($className);
            return \true;
        } catch (IdentifierNotFound $e) {
            return \false;
        } catch (InvalidIdentifierName $e) {
            return \false;
        }
    }
    public function getClass(string $className): ClassReflection
    {
        if (isset(self::$anonymousClasses[$className])) {
            return self::$anonymousClasses[$className];
        }
        try {
            $reflectionClass = $this->reflector->reflectClass($className);
        } catch (IdentifierNotFound|InvalidIdentifierName $e) {
            throw new ClassNotFoundException($className);
        }
        $reflectionClassName = strtolower($reflectionClass->getName());
        if (array_key_exists($reflectionClassName, $this->classReflections)) {
            return $this->classReflections[$reflectionClassName];
        }
        if ($reflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000) {
            $adaptedClass = new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum($reflectionClass);
        } else {
            $adaptedClass = new ReflectionClass($reflectionClass);
        }
        return $this->classReflections[$reflectionClassName] = $this->classReflectionFactory->create($reflectionClass->getName(), $adaptedClass, null, null, fn(): ?ResolvedPhpDocBlock => $this->stubPhpDocProvider->findClassPhpDoc($reflectionClass->getName()));
    }
    public function getClassName(string $className): string
    {
        if (!$this->hasClass($className)) {
            throw new ClassNotFoundException($className);
        }
        if (isset(self::$anonymousClasses[$className])) {
            return self::$anonymousClasses[$className]->getDisplayName();
        }
        $reflectionClass = $this->reflector->reflectClass($className);
        return $reflectionClass->getName();
    }
    public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
    {
        if (isset($classNode->namespacedName)) {
            throw new ShouldNotHappenException();
        }
        if (!$scope->isInTrait()) {
            $scopeFile = $scope->getFile();
        } else {
            $scopeFile = $scope->getTraitReflection()->getFileName();
            if ($scopeFile === null) {
                $scopeFile = $scope->getFile();
            }
        }
        $className = $this->anonymousClassNameHelper->getAnonymousClassName($classNode, $scopeFile);
        $classNode->name = new Node\Identifier($className);
        $classNode->namespacedName = null;
        if (isset(self::$anonymousClasses[$className])) {
            return self::$anonymousClasses[$className];
        }
        $reflectionClass = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromNode($this->reflector, $classNode, new LocatedSource($this->deduplicatingFileReader->read($scopeFile), $className, $scopeFile), null);
        $displayParentName = $reflectionClass->getParentClassName();
        if ($displayParentName === null) {
            // https://3v4l.org/6FBuP
            $classInterfaceNames = $reflectionClass->getInterfaceNames();
            if ($classInterfaceNames !== []) {
                $displayParentName = $classInterfaceNames[array_key_first($classInterfaceNames)];
            } else {
                $displayParentName = 'class';
            }
        }
        /** @var int|null $classLineIndex */
        $classLineIndex = $classNode->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX);
        $filename = $this->fileHelper->normalizePath($this->relativePathHelper->getRelativePath($scopeFile), '/');
        if ($classLineIndex === null) {
            $displayName = sprintf('%s@anonymous/%s:%s', $displayParentName, $filename, $classNode->getStartLine());
        } else {
            $displayName = sprintf('%s@anonymous/%s:%s:%d', $displayParentName, $filename, $classNode->getStartLine(), $classLineIndex);
        }
        return $this->classReflections[$className] = self::$anonymousClasses[$className] = $this->classReflectionFactory->create($displayName, new ReflectionClass($reflectionClass), $scopeFile, null, fn(): ?ResolvedPhpDocBlock => $this->stubPhpDocProvider->findClassPhpDoc($className));
    }
    public function getUniversalObjectCratesClasses(): array
    {
        return $this->universalObjectCratesClasses;
    }
    public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
    {
        return $this->resolveFunctionName($nameNode, $namespaceAnswerer) !== null;
    }
    public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
    {
        $functionName = $this->resolveFunctionName($nameNode, $namespaceAnswerer);
        if ($functionName === null) {
            throw new FunctionNotFoundException((string) $nameNode);
        }
        $lowerCasedFunctionName = strtolower($functionName);
        if (isset($this->functionReflections[$lowerCasedFunctionName])) {
            return $this->functionReflections[$lowerCasedFunctionName];
        }
        if (in_array($lowerCasedFunctionName, ['exit', 'die'], \true)) {
            return $this->functionReflections[$lowerCasedFunctionName] = new ExitFunctionReflection($lowerCasedFunctionName);
        }
        $nativeFunctionReflection = $this->nativeFunctionReflectionProvider->findFunctionReflection($lowerCasedFunctionName);
        if ($nativeFunctionReflection !== null) {
            $this->functionReflections[$lowerCasedFunctionName] = $nativeFunctionReflection;
            return $nativeFunctionReflection;
        }
        $this->functionReflections[$lowerCasedFunctionName] = $this->getCustomFunction($functionName);
        return $this->functionReflections[$lowerCasedFunctionName];
    }
    private function getCustomFunction(string $functionName): PhpFunctionReflection
    {
        $reflectionFunction = new ReflectionFunction($this->reflector->reflectFunction($functionName));
        $templateTypeMap = TemplateTypeMap::createEmpty();
        $phpDocParameterTypes = [];
        $phpDocReturnTag = null;
        $phpDocThrowsTag = null;
        $deprecation = $this->deprecationProvider->getFunctionDeprecation($reflectionFunction);
        $deprecationDescription = $deprecation === null ? null : $deprecation->description;
        $isDeprecated = $deprecation !== null;
        $isInternal = \false;
        $isPure = null;
        $asserts = Assertions::createEmpty();
        $acceptsNamedArguments = \true;
        $phpDocComment = null;
        $phpDocParameterOutTags = [];
        $phpDocParameterImmediatelyInvokedCallable = [];
        $phpDocParameterClosureThisTypeTags = [];
        $phpDocParameterPureUnlessCallableIsImpure = [];
        $phpDocParameterPureUnlessParameterPassed = [];
        $resolvedPhpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($reflectionFunction->betterReflectionFunction->getName(), array_map(static fn(ReflectionParameter $parameter): string => $parameter->betterReflectionParameter->getName(), $reflectionFunction->getParameters()));
        if ($resolvedPhpDoc === null && $reflectionFunction->getFileName() !== \false && ($reflectionFunction->betterReflectionFunction->getDocComment() ?? \false) !== \false) {
            $docComment = $reflectionFunction->betterReflectionFunction->getDocComment() ?? \false;
            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($reflectionFunction->getFileName(), null, null, $reflectionFunction->betterReflectionFunction->getName(), $docComment);
        }
        if ($resolvedPhpDoc !== null) {
            $templateTypeMap = $resolvedPhpDoc->templateTypeMap;
            $phpDocParameterTypes = array_map(static fn($tag) => $tag->type, $resolvedPhpDoc->getParamTags());
            $phpDocReturnTag = $resolvedPhpDoc->getReturnTag();
            $phpDocThrowsTag = $resolvedPhpDoc->getThrowsTag();
            if (!$isDeprecated) {
                $deprecationDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->message : $deprecationDescription;
                $isDeprecated = $resolvedPhpDoc->isDeprecated();
            }
            $isInternal = $resolvedPhpDoc->isInternal();
            $isPure = $resolvedPhpDoc->isPure();
            $asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
            if ($resolvedPhpDoc->hasPhpDocString()) {
                $phpDocComment = $resolvedPhpDoc->phpDocString;
            }
            $acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
            $phpDocParameterOutTags = $resolvedPhpDoc->getParamOutTags();
            $phpDocParameterImmediatelyInvokedCallable = $resolvedPhpDoc->getParamsImmediatelyInvokedCallable();
            $phpDocParameterClosureThisTypeTags = $resolvedPhpDoc->getParamClosureThisTags();
            $phpDocParameterPureUnlessCallableIsImpure = $resolvedPhpDoc->getParamsPureUnlessCallableIsImpure();
            $phpDocParameterPureUnlessParameterPassed = $resolvedPhpDoc->getParamsPureUnlessParameterPassed();
        }
        $lowerCasedFunctionName = strtolower($reflectionFunction->betterReflectionFunction->getName());
        if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
            $functionMetadata = $this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName);
            foreach ($functionMetadata['pureUnlessCallableIsImpureParameters'] ?? [] as $parameterName => $isPureUnlessCallableIsImpure) {
                if (($phpDocParameterPureUnlessCallableIsImpure[$parameterName] ?? \false) === \true) {
                    continue;
                }
                $phpDocParameterPureUnlessCallableIsImpure[$parameterName] = $isPureUnlessCallableIsImpure;
            }
            foreach ($functionMetadata['pureUnlessParameterPassedParameters'] ?? [] as $parameterName => $isPureUnlessParameterPassed) {
                if (($phpDocParameterPureUnlessParameterPassed[$parameterName] ?? \false) === \true) {
                    continue;
                }
                $phpDocParameterPureUnlessParameterPassed[$parameterName] = $isPureUnlessParameterPassed;
            }
        }
        return $this->functionReflectionFactory->create($reflectionFunction, $templateTypeMap, $phpDocParameterTypes, $phpDocReturnTag !== null ? $phpDocReturnTag->type : null, $phpDocThrowsTag !== null ? $phpDocThrowsTag->type : null, $deprecationDescription, $isDeprecated, $isInternal, $reflectionFunction->getFileName() !== \false ? $reflectionFunction->getFileName() : null, $isPure, $asserts, $acceptsNamedArguments, $phpDocComment, array_map(static fn(ParamOutTag $paramOutTag): Type => $paramOutTag->type, $phpDocParameterOutTags), $phpDocParameterImmediatelyInvokedCallable, array_map(static fn(ParamClosureThisTag $tag): Type => $tag->type, $phpDocParameterClosureThisTypeTags), $this->attributeReflectionFactory->fromNativeReflection($reflectionFunction->getAttributes(), InitializerExprContext::fromFunction($reflectionFunction->betterReflectionFunction->getName(), $reflectionFunction->getFileName() !== \false ? $reflectionFunction->getFileName() : null)), $phpDocParameterPureUnlessCallableIsImpure, $phpDocParameterPureUnlessParameterPassed);
    }
    public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
    {
        $name = strtolower($nameNode->name);
        if (in_array($name, ['exit', 'die'], \true)) {
            return $name;
        }
        // memoized per (namespace, name AS WRITTEN): the namespaced-fallback
        // probe of an unqualified name is exception-driven in the reflector, and
        // type callbacks re-ask the same names many times per file. The key is
        // case-sensitive - the resolved name preserves the asked case, which the
        // incorrect-case rules compare against the canonical one - and keeps the
        // leading backslash Name::__toString() drops: `\strlen()` and `strlen()`
        // resolve differently in a namespace declaring its own strlen()
        $cacheKey = $this->nameResolutionCacheKey($nameNode, $namespaceAnswerer);
        if (array_key_exists($cacheKey, $this->resolvedFunctionNames)) {
            $cached = $this->resolvedFunctionNames[$cacheKey];
            return $cached === \false ? null : $cached;
        }
        $resolved = $this->resolveName($nameNode, function (string $name): bool {
            try {
                $this->reflector->reflectFunction($name);
                return \true;
            } catch (IdentifierNotFound $e) {
                // pass
            } catch (InvalidIdentifierName $e) {
                // pass
            }
            if ($this->nativeFunctionReflectionProvider->findFunctionReflection($name) !== null) {
                return $this->phpstormStubsSourceStubber->isPresentFunction($name) !== \false;
            }
            return \false;
        }, $namespaceAnswerer);
        $this->resolvedFunctionNames[$cacheKey] = $resolved ?? \false;
        return $resolved;
    }
    public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
    {
        return $this->resolveConstantName($nameNode, $namespaceAnswerer) !== null;
    }
    public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ConstantReflection
    {
        $constantName = $this->resolveConstantName($nameNode, $namespaceAnswerer);
        if ($constantName === null) {
            throw new ConstantNotFoundException((string) $nameNode);
        }
        $phpVersionType = null;
        $versionKey = 'current_version';
        if ($namespaceAnswerer instanceof Scope) {
            $phpVersionType = $namespaceAnswerer->getPhpVersion()->phpVersions;
            $versionKey = $phpVersionType->describe(VerbosityLevel::cache());
        }
        if (!array_key_exists($versionKey, $this->cachedConstants)) {
            $this->cachedConstants[$versionKey] = [];
        }
        if (array_key_exists($constantName, $this->cachedConstants[$versionKey])) {
            return $this->cachedConstants[$versionKey][$constantName];
        }
        $constantReflection = $this->reflector->reflectConstant($constantName);
        $fileName = $constantReflection->locatedSource->getFileName();
        $constantValueType = $this->initializerExprTypeResolver->getType($constantReflection->getValueExpression(), InitializerExprContext::fromGlobalConstant($constantReflection));
        $docComment = $constantReflection->docComment;
        $deprecation = $this->deprecationProvider->getConstantDeprecation($constantReflection);
        $isDeprecated = $deprecation !== null;
        $deprecatedDescription = $deprecation === null ? null : $deprecation->description;
        if ($isDeprecated === \false && $docComment !== null) {
            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, null, $docComment);
            $isDeprecated = $resolvedPhpDoc->isDeprecated();
            if ($isDeprecated && $resolvedPhpDoc->getDeprecatedTag() !== null) {
                $deprecatedMessage = $resolvedPhpDoc->getDeprecatedTag()->message;
                $matches = Strings::match($deprecatedMessage ?? '', '#^(\d+)\.(\d+)(?:\.(\d+))?$#');
                if ($matches !== null) {
                    $major = (int) $matches[1];
                    $minor = (int) $matches[2];
                    $patch = (int) ($matches[3] ?? 0);
                    $versionId = sprintf('%d%02d%02d', $major, $minor, $patch);
                    if ($phpVersionType !== null) {
                        $isDeprecated = IntegerRangeType::fromInterval((int) $versionId, null)->isSuperTypeOf($phpVersionType)->yes();
                    } else {
                        $isDeprecated = $this->phpVersion->versionId >= $versionId;
                    }
                } else {
                    // filter raw version number messages like in
                    // https://github.com/JetBrains/phpstorm-stubs/blob/9608c953230b08f07b703ecfe459cc58d5421437/filter/filter.php#L478
                    $deprecatedDescription = $deprecatedMessage;
                }
            } elseif (!$isDeprecated) {
                $isDeprecated = \PHPStan\BetterReflection\Reflection\Deprecated\DeprecatedHelper::isDeprecated($constantReflection);
            }
        } elseif (!$isDeprecated) {
            $isDeprecated = \PHPStan\BetterReflection\Reflection\Deprecated\DeprecatedHelper::isDeprecated($constantReflection);
        }
        return $this->cachedConstants[$versionKey][$constantName] = new RuntimeConstantReflection($constantName, $constantValueType, $fileName, TrinaryLogic::createFromBoolean($isDeprecated), $deprecatedDescription, $this->attributeReflectionFactory->fromNativeReflection(array_map(static fn(BetterReflectionAttribute $betterReflectionAttribute) => ReflectionAttributeFactory::create($betterReflectionAttribute), $constantReflection->attributes), InitializerExprContext::fromGlobalConstant($constantReflection)), $constantReflection->locatedSource->isInternal());
    }
    public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
    {
        // memoized per (namespace, name as written) - see resolveFunctionName()
        $cacheKey = $this->nameResolutionCacheKey($nameNode, $namespaceAnswerer);
        if (array_key_exists($cacheKey, $this->resolvedConstantNames)) {
            $cached = $this->resolvedConstantNames[$cacheKey];
            return $cached === \false ? null : $cached;
        }
        $resolved = $this->resolveName($nameNode, function (string $name): bool {
            try {
                $this->reflector->reflectConstant($name);
                return \true;
            } catch (IdentifierNotFound $e) {
                // pass
            } catch (InvalidIdentifierName $e) {
                // pass
            } catch (UnableToCompileNode $e) {
                // pass
            }
            return \false;
        }, $namespaceAnswerer);
        $this->resolvedConstantNames[$cacheKey] = $resolved ?? \false;
        return $resolved;
    }
    private function nameResolutionCacheKey(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): string
    {
        return ($namespaceAnswerer !== null ? $namespaceAnswerer->getNamespace() ?? '' : '') . '::' . ($nameNode->isFullyQualified() ? '\\' : '') . (string) $nameNode;
    }
    /**
     * @param Closure(string $name): bool $existsCallback
     */
    private function resolveName(Node\Name $nameNode, Closure $existsCallback, ?NamespaceAnswerer $namespaceAnswerer): ?string
    {
        $name = (string) $nameNode;
        if ($namespaceAnswerer !== null && $namespaceAnswerer->getNamespace() !== null && !$nameNode->isFullyQualified()) {
            $namespacedName = sprintf('%s\%s', $namespaceAnswerer->getNamespace(), $name);
            if ($existsCallback($namespacedName)) {
                return $namespacedName;
            }
        }
        if ($existsCallback($name)) {
            return $name;
        }
        return null;
    }
}
