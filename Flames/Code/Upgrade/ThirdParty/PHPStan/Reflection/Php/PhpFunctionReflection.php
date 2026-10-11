<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterAllowedConstantsMapProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function is_file;
use function strtolower;
#[GenerateFactory(FunctionReflectionFactory::class)]
final class PhpFunctionReflection implements FunctionReflection
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionFunction $reflection;
    private AttributeReflectionFactory $attributeReflectionFactory;
    private ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider;
    private TemplateTypeMap $templateTypeMap;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterTypes;
    private ?Type $phpDocReturnType;
    private ?Type $phpDocThrowType;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    private ?string $filename;
    private ?bool $isPure;
    private Assertions $asserts;
    private bool $acceptsNamedArguments;
    private ?string $phpDocComment;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterOutTypes;
    /**
     * @var array<string, bool>
     */
    private array $phpDocParameterImmediatelyInvokedCallable;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterClosureThisTypes;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    /**
     * @var array<string, bool>
     */
    private array $phpDocParameterPureUnlessCallableIsImpure;
    /**
     * @var array<string, bool>
     */
    private array $phpDocParameterPureUnlessParameterPassed;
    /** @var list<ExtendedFunctionVariant>|null */
    private ?array $variants = null;
    /**
     * @param array<string, Type> $phpDocParameterTypes
     * @param array<string, Type> $phpDocParameterOutTypes
     * @param array<string, bool> $phpDocParameterImmediatelyInvokedCallable
     * @param array<string, Type> $phpDocParameterClosureThisTypes
     * @param list<AttributeReflection> $attributes
     * @param array<string, bool> $phpDocParameterPureUnlessCallableIsImpure
     * @param array<string, bool> $phpDocParameterPureUnlessParameterPassed
     */
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionFunction $reflection, AttributeReflectionFactory $attributeReflectionFactory, ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider, TemplateTypeMap $templateTypeMap, array $phpDocParameterTypes, ?Type $phpDocReturnType, ?Type $phpDocThrowType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, ?string $filename, ?bool $isPure, Assertions $asserts, bool $acceptsNamedArguments, ?string $phpDocComment, array $phpDocParameterOutTypes, array $phpDocParameterImmediatelyInvokedCallable, array $phpDocParameterClosureThisTypes, array $attributes, array $phpDocParameterPureUnlessCallableIsImpure, array $phpDocParameterPureUnlessParameterPassed)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflection = $reflection;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->allowedConstantsMapProvider = $allowedConstantsMapProvider;
        $this->templateTypeMap = $templateTypeMap;
        $this->phpDocParameterTypes = $phpDocParameterTypes;
        $this->phpDocReturnType = $phpDocReturnType;
        $this->phpDocThrowType = $phpDocThrowType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->filename = $filename;
        $this->isPure = $isPure;
        $this->asserts = $asserts;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->phpDocComment = $phpDocComment;
        $this->phpDocParameterOutTypes = $phpDocParameterOutTypes;
        $this->phpDocParameterImmediatelyInvokedCallable = $phpDocParameterImmediatelyInvokedCallable;
        $this->phpDocParameterClosureThisTypes = $phpDocParameterClosureThisTypes;
        $this->attributes = $attributes;
        $this->phpDocParameterPureUnlessCallableIsImpure = $phpDocParameterPureUnlessCallableIsImpure;
        $this->phpDocParameterPureUnlessParameterPassed = $phpDocParameterPureUnlessParameterPassed;
    }
    public function getName(): string
    {
        return $this->reflection->betterReflectionFunction->getName();
    }
    public function getFileName(): ?string
    {
        if ($this->filename === null) {
            return null;
        }
        if (!is_file($this->filename)) {
            return null;
        }
        return $this->filename;
    }
    public function getVariants(): array
    {
        return $this->variants ??= [new ExtendedFunctionVariant($this->templateTypeMap, null, $this->getParameters(), $this->reflection->isVariadic(), \PHPStan\Type\TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType(), $this->phpDocReturnType), $this->getPhpDocReturnType(), \PHPStan\Type\TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType()))];
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
        return array_map(function (ReflectionParameter $reflection): \PHPStan\Reflection\Php\PhpParameterReflection {
            if (array_key_exists($reflection->betterReflectionParameter->getName(), $this->phpDocParameterImmediatelyInvokedCallable)) {
                $immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($this->phpDocParameterImmediatelyInvokedCallable[$reflection->betterReflectionParameter->getName()]);
            } else {
                $immediatelyInvokedCallable = TrinaryLogic::createMaybe();
            }
            return new \PHPStan\Reflection\Php\PhpParameterReflection($this->initializerExprTypeResolver, $reflection, $this->phpDocParameterTypes[$reflection->betterReflectionParameter->getName()] ?? null, null, $this->phpDocParameterOutTypes[$reflection->betterReflectionParameter->getName()] ?? null, $immediatelyInvokedCallable, $this->phpDocParameterClosureThisTypes[$reflection->betterReflectionParameter->getName()] ?? null, $this->attributeReflectionFactory->fromNativeReflection($reflection->getAttributes(), InitializerExprContext::fromReflectionParameter($reflection)), $this->allowedConstantsMapProvider->getForFunctionParameter(strtolower($this->reflection->betterReflectionFunction->getName()), $reflection->betterReflectionParameter->getName()), TrinaryLogic::createFromBoolean($this->phpDocParameterPureUnlessCallableIsImpure[$reflection->betterReflectionParameter->getName()] ?? \false), TrinaryLogic::createFromBoolean($this->phpDocParameterPureUnlessParameterPassed[$reflection->betterReflectionParameter->getName()] ?? \false));
        }, $this->reflection->getParameters());
    }
    private function isVariadic(): bool
    {
        return $this->reflection->betterReflectionFunction->isVariadic();
    }
    private function getReturnType(): Type
    {
        return TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionFunction->getReturnType()), $this->phpDocReturnType);
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
        return TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionFunction->getReturnType()));
    }
    public function getDeprecatedDescription(): ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        if ($this->reflection->betterReflectionFunction->isDeprecated()) {
            $attributes = $this->reflection->betterReflectionFunction->getAttributes();
            return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
        }
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated || $this->reflection->betterReflectionFunction->isDeprecated());
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function getThrowType(): ?Type
    {
        return $this->phpDocThrowType;
    }
    public function hasSideEffects(): TrinaryLogic
    {
        if (\PHPStan\Type\TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType(), $this->phpDocReturnType)->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        return TrinaryLogic::createMaybe();
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
        return array_map(static fn(bool $value): TrinaryLogic => TrinaryLogic::createFromBoolean($value), $this->phpDocParameterPureUnlessCallableIsImpure);
    }
    /**
     * @return array<string, TrinaryLogic>
     */
    public function getPureUnlessParameterPassedParameters(): array
    {
        return array_map(static fn(bool $value): TrinaryLogic => TrinaryLogic::createFromBoolean($value), $this->phpDocParameterPureUnlessParameterPassed);
    }
    public function isBuiltin(): bool
    {
        return $this->reflection->betterReflectionFunction->isInternal();
    }
    public function getAsserts(): Assertions
    {
        return $this->asserts;
    }
    public function getDocComment(): ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionFunction->returnsReference());
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->acceptsNamedArguments);
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
}
