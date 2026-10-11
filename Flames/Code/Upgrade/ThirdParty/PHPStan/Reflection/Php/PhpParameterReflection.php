<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Reflection\AllowedConstantsResult;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterAllowedConstants;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/PhpParameterReflection.cpp')]
final class PhpParameterReflection implements ExtendedParameterReflection
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionParameter $reflection;
    private ?Type $phpDocType;
    private ?ClassReflection $declaringClass;
    private ?Type $outType;
    private TrinaryLogic $immediatelyInvokedCallable;
    private ?Type $closureThisType;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private ?ParameterAllowedConstants $allowedConstants;
    private TrinaryLogic $pureUnlessCallableIsImpureParameter;
    private TrinaryLogic $pureUnlessParameterPassedParameter;
    private ?Type $type = null;
    private ?Type $nativeType = null;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionParameter $reflection, ?Type $phpDocType, ?ClassReflection $declaringClass, ?Type $outType, TrinaryLogic $immediatelyInvokedCallable, ?Type $closureThisType, array $attributes, ?ParameterAllowedConstants $allowedConstants, TrinaryLogic $pureUnlessCallableIsImpureParameter, TrinaryLogic $pureUnlessParameterPassedParameter)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflection = $reflection;
        $this->phpDocType = $phpDocType;
        $this->declaringClass = $declaringClass;
        $this->outType = $outType;
        $this->immediatelyInvokedCallable = $immediatelyInvokedCallable;
        $this->closureThisType = $closureThisType;
        $this->attributes = $attributes;
        $this->allowedConstants = $allowedConstants;
        $this->pureUnlessCallableIsImpureParameter = $pureUnlessCallableIsImpureParameter;
        $this->pureUnlessParameterPassedParameter = $pureUnlessParameterPassedParameter;
    }
    public function isOptional(): bool
    {
        return $this->reflection->betterReflectionParameter->isOptional();
    }
    public function getName(): string
    {
        return $this->reflection->betterReflectionParameter->getName();
    }
    public function getType(): Type
    {
        if ($this->type === null) {
            $phpDocType = $this->phpDocType;
            if ($phpDocType !== null && $this->reflection->betterReflectionParameter->isDefaultValueAvailable()) {
                $defaultValueType = $this->initializerExprTypeResolver->getType($this->reflection->betterReflectionParameter->getDefaultValueExpression(), InitializerExprContext::fromReflectionParameter($this->reflection));
                if ($defaultValueType->isNull()->yes()) {
                    $phpDocType = TypeCombinator::addNull($phpDocType);
                }
            }
            $this->type = TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionParameter->getType()), $phpDocType, $this->declaringClass, $this->isVariadic());
        }
        return $this->type;
    }
    public function passedByReference(): PassedByReference
    {
        return $this->reflection->betterReflectionParameter->isPassedByReference() ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo();
    }
    public function isVariadic(): bool
    {
        return $this->reflection->betterReflectionParameter->isVariadic();
    }
    public function getPhpDocType(): Type
    {
        if ($this->phpDocType !== null) {
            return $this->phpDocType;
        }
        return new MixedType();
    }
    public function hasNativeType(): bool
    {
        return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionParameter->getType()) !== null;
    }
    public function getNativeType(): Type
    {
        return $this->nativeType ??= TypehintHelper::decideTypeFromReflection(\PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->reflection->betterReflectionParameter->getType()), \null, $this->declaringClass, $this->isVariadic());
    }
    public function getDefaultValue(): ?Type
    {
        if ($this->reflection->betterReflectionParameter->isDefaultValueAvailable()) {
            return $this->initializerExprTypeResolver->getType($this->reflection->betterReflectionParameter->getDefaultValueExpression(), InitializerExprContext::fromReflectionParameter($this->reflection));
        }
        return null;
    }
    public function getOutType(): ?Type
    {
        return $this->outType;
    }
    public function isImmediatelyInvokedCallable(): TrinaryLogic
    {
        return $this->immediatelyInvokedCallable;
    }
    public function getClosureThisType(): ?Type
    {
        return $this->closureThisType;
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function getAllowedConstants(): ?ParameterAllowedConstants
    {
        return $this->allowedConstants;
    }
    public function checkAllowedConstants(array $constants): AllowedConstantsResult
    {
        if ($this->allowedConstants === null) {
            return new AllowedConstantsResult([], [], \false);
        }
        return $this->allowedConstants->check($constants);
    }
    public function isPureUnlessCallableIsImpureParameter(): TrinaryLogic
    {
        return $this->pureUnlessCallableIsImpureParameter;
    }
    public function isPureUnlessParameterPassedParameter(): TrinaryLogic
    {
        return $this->pureUnlessParameterPassedParameter;
    }
}
