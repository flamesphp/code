<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParameterAllowedConstantsMapProvider;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;
use ReflectionException;
use function array_map;
use function explode;
use function in_array;
use function strtolower;
use const PHP_VERSION_ID;
/**
 * @api
 */
#[GenerateFactory(\PHPStan\Reflection\Php\PhpMethodReflectionFactory::class)]
#[ReferencedByTurboExtension('phpMethodReflection')]
final class PhpMethodReflection implements ExtendedMethodReflection
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ClassReflection $declaringClass;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?ClassReflection $declaringTrait;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ReflectionMethod $reflection;
    private ReflectionProvider $reflectionProvider;
    private AttributeReflectionFactory $attributeReflectionFactory;
    private ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider;
    private TemplateTypeMap $templateTypeMap;
    /**
     * @var Type[]
     */
    private array $phpDocParameterTypes;
    private ?Type $phpDocReturnType;
    private ?Type $phpDocThrowType;
    private ?ResolvedPhpDocBlock $resolvedPhpDocBlock;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isFinal;
    private ?bool $isPure;
    private Assertions $asserts;
    private bool $acceptsNamedArguments;
    private ?Type $selfOutType;
    private ?string $phpDocComment;
    /**
     * @var Type[]
     */
    private array $phpDocParameterOutTypes;
    /**
     * @var array<string, TrinaryLogic>
     */
    private array $immediatelyInvokedCallableParameters;
    /**
     * @var array<string, Type>
     */
    private array $phpDocClosureThisTypeParameters;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    /**
     * @var array<string, bool>
     */
    private array $pureUnlessCallableIsImpureParameters;
    /**
     * @var array<string, bool>
     */
    private array $pureUnlessParameterPassedParameters;
    /** @var list<PhpParameterReflection>|null */
    private ?array $parameters = null;
    private ?Type $returnType = null;
    private ?Type $nativeReturnType = null;
    /** @var list<ExtendedFunctionVariant>|null */
    private ?array $variants = null;
    private ?string $name = null;
    /**
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $phpDocParameterOutTypes
     * @param array<string, TrinaryLogic> $immediatelyInvokedCallableParameters
     * @param array<string, Type> $phpDocClosureThisTypeParameters
     * @param list<AttributeReflection> $attributes
     * @param array<string, bool> $pureUnlessCallableIsImpureParameters
     * @param array<string, bool> $pureUnlessParameterPassedParameters
     */
    public function __construct(
        InitializerExprTypeResolver $initializerExprTypeResolver,
        ClassReflection $declaringClass,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?ClassReflection $declaringTrait,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ReflectionMethod $reflection,
        ReflectionProvider $reflectionProvider,
        AttributeReflectionFactory $attributeReflectionFactory,
        ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider,
        TemplateTypeMap $templateTypeMap,
        array $phpDocParameterTypes,
        ?Type $phpDocReturnType,
        ?Type $phpDocThrowType,
        ?ResolvedPhpDocBlock $resolvedPhpDocBlock,
        ?string $deprecatedDescription,
        bool $isDeprecated,
        bool $isInternal,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isFinal,
        ?bool $isPure,
        Assertions $asserts,
        bool $acceptsNamedArguments,
        ?Type $selfOutType,
        ?string $phpDocComment,
        array $phpDocParameterOutTypes,
        array $immediatelyInvokedCallableParameters,
        array $phpDocClosureThisTypeParameters,
        array $attributes,
        array $pureUnlessCallableIsImpureParameters,
        array $pureUnlessParameterPassedParameters
    )
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->declaringClass = $declaringClass;
        $this->declaringTrait = $declaringTrait;
        $this->reflection = $reflection;
        $this->reflectionProvider = $reflectionProvider;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->allowedConstantsMapProvider = $allowedConstantsMapProvider;
        $this->templateTypeMap = $templateTypeMap;
        $this->phpDocParameterTypes = $phpDocParameterTypes;
        $this->phpDocReturnType = $phpDocReturnType;
        $this->phpDocThrowType = $phpDocThrowType;
        $this->resolvedPhpDocBlock = $resolvedPhpDocBlock;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isFinal = $isFinal;
        $this->isPure = $isPure;
        $this->asserts = $asserts;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->selfOutType = $selfOutType;
        $this->phpDocComment = $phpDocComment;
        $this->phpDocParameterOutTypes = $phpDocParameterOutTypes;
        $this->immediatelyInvokedCallableParameters = $immediatelyInvokedCallableParameters;
        $this->phpDocClosureThisTypeParameters = $phpDocClosureThisTypeParameters;
        $this->attributes = $attributes;
        $this->pureUnlessCallableIsImpureParameters = $pureUnlessCallableIsImpureParameters;
        $this->pureUnlessParameterPassedParameters = $pureUnlessParameterPassedParameters;
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function getDeclaringTrait(): ?ClassReflection
    {
        return $this->declaringTrait;
    }
    /**
     * @return self|MethodPrototypeReflection
     */
    public function getPrototype(): ClassMemberReflection
    {
        try {
            $prototypeMethod = $this->reflection->getPrototype();
            $prototypeDeclaringClass = $this->declaringClass->getAncestorWithClassName($prototypeMethod->getDeclaringClass()->betterReflectionClass->getName());
            if ($prototypeDeclaringClass === null) {
                $prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->betterReflectionClass->getName());
            }
            if (!$prototypeDeclaringClass->hasNativeMethod($prototypeMethod->betterReflectionMethod->getName())) {
                return $this;
            }
            $tentativeReturnType = null;
            if (\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($prototypeMethod->betterReflectionMethod->getTentativeReturnType()) !== null) {
                $tentativeReturnType = TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($prototypeMethod->betterReflectionMethod->getTentativeReturnType()), \null, $prototypeDeclaringClass);
            }
            return new MethodPrototypeReflection($prototypeMethod->betterReflectionMethod->getName(), $prototypeDeclaringClass, $prototypeMethod->betterReflectionMethod->isStatic(), $prototypeMethod->betterReflectionMethod->isPrivate(), $prototypeMethod->betterReflectionMethod->isPublic(), $prototypeMethod->betterReflectionMethod->isAbstract(), $prototypeMethod->betterReflectionMethod->isInternal(), $prototypeDeclaringClass->getNativeMethod($prototypeMethod->betterReflectionMethod->getName())->getVariants(), $tentativeReturnType);
        } catch (ReflectionException $e) {
            return $this;
        }
    }
    public function isStatic(): bool
    {
        return $this->reflection->betterReflectionMethod->isStatic();
    }
    public function getName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }
        $name = $this->reflection->betterReflectionMethod->getName();
        $lowercaseName = strtolower($name);
        if ($lowercaseName === $name) {
            if (PHP_VERSION_ID >= 80000) {
                return $this->name = $name;
            }
            // fix for https://bugs.php.net/bug.php?id=74939
            foreach ($this->getDeclaringClass()->getNativeReflection()->getTraitAliases() as $traitTarget) {
                $correctName = $this->getMethodNameWithCorrectCase($name, $traitTarget);
                if ($correctName !== null) {
                    $name = $correctName;
                    break;
                }
            }
        }
        return $this->name = $name;
    }
    private function getMethodNameWithCorrectCase(string $lowercaseMethodName, string $traitTarget): ?string
    {
        $trait = explode('::', $traitTarget)[0];
        $traitReflection = $this->reflectionProvider->getClass($trait)->getNativeReflection();
        foreach ($traitReflection->getTraitAliases() as $methodAlias => $aliasTraitTarget) {
            if ($lowercaseMethodName === strtolower($methodAlias)) {
                return $methodAlias;
            }
            $correctName = $this->getMethodNameWithCorrectCase($lowercaseMethodName, $aliasTraitTarget);
            if ($correctName !== null) {
                return $correctName;
            }
        }
        return null;
    }
    /**
     * @return list<ExtendedParametersAcceptor>
     */
    public function getVariants(): array
    {
        return $this->variants ??= [new ExtendedFunctionVariant($this->templateTypeMap, null, $this->getParameters(), $this->reflection->isVariadic(), $this->getReturnType(), $this->getPhpDocReturnType(), $this->getNativeReturnType())];
    }
    public function getOnlyVariant(): ExtendedParametersAcceptor
    {
        return $this->getVariants()[0];
    }
    public function getNamedArgumentsVariants(): ?array
    {
        return null;
    }
    /**
     * @return list<ExtendedParameterReflection>
     */
    private function getParameters(): array
    {
        return $this->parameters ??= array_map(fn(ReflectionParameter $reflection): \PHPStan\Reflection\Php\PhpParameterReflection => new \PHPStan\Reflection\Php\PhpParameterReflection($this->initializerExprTypeResolver, $reflection, $this->phpDocParameterTypes[$reflection->betterReflectionParameter->getName()] ?? null, $this->declaringClass, $this->phpDocParameterOutTypes[$reflection->betterReflectionParameter->getName()] ?? null, $this->immediatelyInvokedCallableParameters[$reflection->betterReflectionParameter->getName()] ?? TrinaryLogic::createMaybe(), $this->phpDocClosureThisTypeParameters[$reflection->betterReflectionParameter->getName()] ?? null, $this->attributeReflectionFactory->fromNativeReflection($reflection->getAttributes(), InitializerExprContext::fromReflectionParameter($reflection)), $this->allowedConstantsMapProvider->getForMethodParameter($this->declaringClass->getName(), $this->reflection->betterReflectionMethod->getName(), $reflection->betterReflectionParameter->getName()), TrinaryLogic::createFromBoolean($this->pureUnlessCallableIsImpureParameters[$reflection->betterReflectionParameter->getName()] ?? \false), TrinaryLogic::createFromBoolean($this->pureUnlessParameterPassedParameters[$reflection->betterReflectionParameter->getName()] ?? \false)), $this->reflection->getParameters());
    }
    private function isVariadic(): bool
    {
        return $this->reflection->betterReflectionMethod->isVariadic();
    }
    public function isPrivate(): bool
    {
        return $this->reflection->betterReflectionMethod->isPrivate();
    }
    public function isPublic(): bool
    {
        return $this->reflection->betterReflectionMethod->isPublic();
    }
    private function getReturnType(): Type
    {
        if ($this->returnType === null) {
            $name = strtolower($this->getName());
            $returnType = \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionMethod->getReturnType());
            if ($returnType === null) {
                if (in_array($name, ['__construct', '__destruct', '__unset', '__wakeup', '__clone'], \true)) {
                    return $this->returnType = TypehintHelper::decideType(new VoidType(), $this->phpDocReturnType);
                }
                if ($name === '__tostring') {
                    return $this->returnType = TypehintHelper::decideType(new StringType(), $this->phpDocReturnType);
                }
                if ($name === '__isset') {
                    return $this->returnType = TypehintHelper::decideType(new BooleanType(), $this->phpDocReturnType);
                }
                if ($name === '__sleep') {
                    return $this->returnType = TypehintHelper::decideType(new ArrayType(new IntegerType(), new StringType()), $this->phpDocReturnType);
                }
                if ($name === '__set_state') {
                    return $this->returnType = TypehintHelper::decideType(new ObjectWithoutClassType(), $this->phpDocReturnType);
                }
            }
            $this->returnType = TypehintHelper::decideTypeFromReflection($returnType, $this->phpDocReturnType, $this->declaringClass);
        }
        return $this->returnType;
    }
    private function getPhpDocReturnType(): Type
    {
        if ($this->phpDocReturnType !== null) {
            return $this->phpDocReturnType;
        }
        return new MixedType();
    }
    private function getNativeReturnType(): Type
    {
        return $this->nativeReturnType ??= TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionMethod->getReturnType()), \null, $this->declaringClass);
    }
    public function getDeprecatedDescription(): ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        if ($this->reflection->betterReflectionMethod->isDeprecated()) {
            $attributes = $this->reflection->betterReflectionMethod->getAttributes();
            return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
        }
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        if ($this->isDeprecated) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionMethod->isDeprecated());
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function isBuiltin(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionMethod->isInternal());
    }
    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isFinal || $this->reflection->betterReflectionMethod->isFinal());
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionMethod->isFinal());
    }
    public function isAbstract(): bool
    {
        return $this->reflection->betterReflectionMethod->isAbstract();
    }
    public function getThrowType(): ?Type
    {
        return $this->phpDocThrowType;
    }
    public function hasSideEffects(): TrinaryLogic
    {
        if (strtolower($this->getName()) !== '__construct' && $this->getReturnType()->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        if ((new ThisType($this->declaringClass))->isSuperTypeOf($this->getReturnType())->yes()) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getAsserts(): Assertions
    {
        return $this->asserts;
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->declaringClass->acceptsNamedArguments() && $this->acceptsNamedArguments);
    }
    public function getSelfOutType(): ?Type
    {
        return $this->selfOutType;
    }
    public function getDocComment(): ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionMethod->returnsReference());
    }
    public function isPure(): TrinaryLogic
    {
        if ($this->isPure === null) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createFromBoolean($this->isPure);
    }
    /**
     * @return array<string, TrinaryLogic>
     */
    public function getPureUnlessCallableIsImpureParameters(): array
    {
        return array_map(static fn(bool $value): TrinaryLogic => TrinaryLogic::createFromBoolean($value), $this->pureUnlessCallableIsImpureParameters);
    }
    /**
     * @return array<string, TrinaryLogic>
     */
    public function getPureUnlessParameterPassedParameters(): array
    {
        return array_map(static fn(bool $value): TrinaryLogic => TrinaryLogic::createFromBoolean($value), $this->pureUnlessParameterPassedParameters);
    }
    public function changePropertyGetHookPhpDocType(Type $phpDocType): self
    {
        return new self($this->initializerExprTypeResolver, $this->declaringClass, $this->declaringTrait, $this->reflection, $this->reflectionProvider, $this->attributeReflectionFactory, $this->allowedConstantsMapProvider, $this->templateTypeMap, $this->phpDocParameterTypes, $phpDocType, $this->phpDocThrowType, $this->resolvedPhpDocBlock, $this->deprecatedDescription, $this->isDeprecated, $this->isInternal, $this->isFinal, $this->isPure, $this->asserts, $this->acceptsNamedArguments, $this->selfOutType, $this->phpDocComment, $this->phpDocParameterOutTypes, $this->immediatelyInvokedCallableParameters, $this->phpDocClosureThisTypeParameters, $this->attributes, $this->pureUnlessCallableIsImpureParameters, $this->pureUnlessParameterPassedParameters);
    }
    public function changePropertySetHookPhpDocType(string $parameterName, Type $phpDocType): self
    {
        $phpDocParameterTypes = $this->phpDocParameterTypes;
        $phpDocParameterTypes[$parameterName] = $phpDocType;
        return new self($this->initializerExprTypeResolver, $this->declaringClass, $this->declaringTrait, $this->reflection, $this->reflectionProvider, $this->attributeReflectionFactory, $this->allowedConstantsMapProvider, $this->templateTypeMap, $phpDocParameterTypes, $this->phpDocReturnType, $this->phpDocThrowType, $this->resolvedPhpDocBlock, $this->deprecatedDescription, $this->isDeprecated, $this->isInternal, $this->isFinal, $this->isPure, $this->asserts, $this->acceptsNamedArguments, $this->selfOutType, $this->phpDocComment, $this->phpDocParameterOutTypes, $this->immediatelyInvokedCallableParameters, $this->phpDocClosureThisTypeParameters, $this->attributes, $this->pureUnlessCallableIsImpureParameters, $this->pureUnlessParameterPassedParameters);
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function mustUseReturnValue(): TrinaryLogic
    {
        foreach ($this->attributes as $attrib) {
            if (strtolower($attrib->name) === 'nodiscard') {
                return TrinaryLogic::createYes();
            }
        }
        return TrinaryLogic::createNo();
    }
    public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
    {
        return $this->resolvedPhpDocBlock;
    }
}
