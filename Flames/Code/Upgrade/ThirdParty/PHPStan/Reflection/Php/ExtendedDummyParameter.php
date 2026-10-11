<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\AllowedConstantsResult;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ParameterAllowedConstants;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/ExtendedDummyParameter.cpp')]
final class ExtendedDummyParameter extends \PHPStan\Reflection\Php\DummyParameter implements ExtendedParameterReflection
{
    private Type $nativeType;
    private Type $phpDocType;
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
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(string $name, Type $type, bool $optional, ?PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue, Type $nativeType, Type $phpDocType, ?Type $outType, TrinaryLogic $immediatelyInvokedCallable, ?Type $closureThisType, array $attributes, ?ParameterAllowedConstants $allowedConstants, TrinaryLogic $pureUnlessCallableIsImpureParameter, TrinaryLogic $pureUnlessParameterPassedParameter)
    {
        $this->nativeType = $nativeType;
        $this->phpDocType = $phpDocType;
        $this->outType = $outType;
        $this->immediatelyInvokedCallable = $immediatelyInvokedCallable;
        $this->closureThisType = $closureThisType;
        $this->attributes = $attributes;
        $this->allowedConstants = $allowedConstants;
        $this->pureUnlessCallableIsImpureParameter = $pureUnlessCallableIsImpureParameter;
        $this->pureUnlessParameterPassedParameter = $pureUnlessParameterPassedParameter;
        parent::__construct($name, $type, $optional, $passedByReference, $variadic, $defaultValue);
    }
    public function getPhpDocType(): Type
    {
        return $this->phpDocType;
    }
    public function hasNativeType(): bool
    {
        return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
    }
    public function getNativeType(): Type
    {
        return $this->nativeType;
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
