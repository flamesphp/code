<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Rules\Properties\FoundPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * One resolved link of an isset/empty/?? chain. IssetabilityDescriptor::resolve()
 * walks the chain once and produces these (the expensive part: types, offset
 * existence, property reflection are resolved here and never again), so the engine
 * (IssetabilityResolution::isSet) and the rule (PHPStan\Rules\IssetCheck) read the
 * facts instead of re-walking and re-resolving.
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/IssetabilityLinkInfo.cpp')]
final class IssetabilityLinkInfo
{
    private string $kind;
    private ?string $variableName;
    private ?TrinaryLogic $hasVariable;
    private ?TrinaryLogic $isOffsetAccessible;
    private ?TrinaryLogic $hasOffsetValue;
    private bool $hasExpressionTypeOfExpr;
    private ?Type $varType;
    private ?Type $dimType;
    private ?Type $valueType;
    private ?FoundPropertyReflection $propertyReflection;
    private ?Expr $propertyFetch;
    private bool $reflectionNative;
    private bool $hasNativeType;
    private ?TrinaryLogic $isVirtual;
    private ?Type $nativeType;
    private bool $hasExpressionTypeOfFetch;
    private bool $initializedThisProperty;
    private bool $nativeReflectionExists;
    private bool $nativeIsPromoted;
    private bool $nativeIsReadOnly;
    private bool $nativeIsHooked;
    private bool $nativeHasDefaultValue;
    private ?Expr $leafExpr;
    private bool $leafIsNullsafePropertyFetch;
    private const KIND_VARIABLE = 'variable';
    private const KIND_OFFSET = 'offset';
    private const KIND_PROPERTY = 'property';
    private const KIND_LEAF = 'leaf';
    private function __construct(string $kind, ?string $variableName = null, ?TrinaryLogic $hasVariable = null, ?TrinaryLogic $isOffsetAccessible = null, ?TrinaryLogic $hasOffsetValue = null, bool $hasExpressionTypeOfExpr = \false, ?Type $varType = null, ?Type $dimType = null, ?Type $valueType = null, ?FoundPropertyReflection $propertyReflection = null, ?Expr $propertyFetch = null, bool $reflectionNative = \false, bool $hasNativeType = \false, ?TrinaryLogic $isVirtual = null, ?Type $nativeType = null, bool $hasExpressionTypeOfFetch = \false, bool $initializedThisProperty = \false, bool $nativeReflectionExists = \false, bool $nativeIsPromoted = \false, bool $nativeIsReadOnly = \false, bool $nativeIsHooked = \false, bool $nativeHasDefaultValue = \false, ?Expr $leafExpr = null, bool $leafIsNullsafePropertyFetch = \false)
    {
        $this->kind = $kind;
        $this->variableName = $variableName;
        $this->hasVariable = $hasVariable;
        $this->isOffsetAccessible = $isOffsetAccessible;
        $this->hasOffsetValue = $hasOffsetValue;
        $this->hasExpressionTypeOfExpr = $hasExpressionTypeOfExpr;
        $this->varType = $varType;
        $this->dimType = $dimType;
        $this->valueType = $valueType;
        $this->propertyReflection = $propertyReflection;
        $this->propertyFetch = $propertyFetch;
        $this->reflectionNative = $reflectionNative;
        $this->hasNativeType = $hasNativeType;
        $this->isVirtual = $isVirtual;
        $this->nativeType = $nativeType;
        $this->hasExpressionTypeOfFetch = $hasExpressionTypeOfFetch;
        $this->initializedThisProperty = $initializedThisProperty;
        $this->nativeReflectionExists = $nativeReflectionExists;
        $this->nativeIsPromoted = $nativeIsPromoted;
        $this->nativeIsReadOnly = $nativeIsReadOnly;
        $this->nativeIsHooked = $nativeIsHooked;
        $this->nativeHasDefaultValue = $nativeHasDefaultValue;
        $this->leafExpr = $leafExpr;
        $this->leafIsNullsafePropertyFetch = $leafIsNullsafePropertyFetch;
    }
    public static function variable(string $variableName, TrinaryLogic $hasVariable, Type $valueType): self
    {
        return new self(self::KIND_VARIABLE, $variableName, $hasVariable, \null, \null, \false, \null, \null, $valueType);
    }
    public static function offset(TrinaryLogic $isOffsetAccessible, TrinaryLogic $hasOffsetValue, bool $hasExpressionTypeOfExpr, Type $varType, Type $dimType, Type $valueType): self
    {
        return new self(self::KIND_OFFSET, \null, \null, $isOffsetAccessible, $hasOffsetValue, $hasExpressionTypeOfExpr, $varType, $dimType, $valueType);
    }
    public static function property(?FoundPropertyReflection $propertyReflection, Expr $propertyFetch, bool $reflectionNative, bool $hasNativeType, TrinaryLogic $isVirtual, Type $writableType, Type $nativeType, bool $hasExpressionTypeOfFetch, bool $initializedThisProperty, bool $nativeReflectionExists, bool $nativeIsPromoted, bool $nativeIsReadOnly, bool $nativeIsHooked, bool $nativeHasDefaultValue): self
    {
        return new self(self::KIND_PROPERTY, \null, \null, \null, \null, \false, \null, \null, $writableType, $propertyReflection, $propertyFetch, $reflectionNative, $hasNativeType, $isVirtual, $nativeType, $hasExpressionTypeOfFetch, $initializedThisProperty, $nativeReflectionExists, $nativeIsPromoted, $nativeIsReadOnly, $nativeIsHooked, $nativeHasDefaultValue);
    }
    public static function leaf(Type $valueType, Expr $leafExpr, bool $leafIsNullsafePropertyFetch): self
    {
        return new self(self::KIND_LEAF, \null, \null, \null, \null, \false, \null, \null, $valueType, \null, \null, \false, \false, \null, \null, \false, \false, \false, \false, \false, \false, \false, $leafExpr, $leafIsNullsafePropertyFetch);
    }
    public function isVariable(): bool
    {
        return $this->kind === self::KIND_VARIABLE;
    }
    public function isOffset(): bool
    {
        return $this->kind === self::KIND_OFFSET;
    }
    public function isProperty(): bool
    {
        return $this->kind === self::KIND_PROPERTY;
    }
    public function getVariableName(): string
    {
        if ($this->variableName === null) {
            throw new ShouldNotHappenException();
        }
        return $this->variableName;
    }
    public function getHasVariable(): TrinaryLogic
    {
        if ($this->hasVariable === null) {
            throw new ShouldNotHappenException();
        }
        return $this->hasVariable;
    }
    /** The type the operator's callback inspects: variable type, offset value type, property writable type, or leaf type. */
    public function getValueType(): Type
    {
        if ($this->valueType === null) {
            throw new ShouldNotHappenException();
        }
        return $this->valueType;
    }
    public function getIsOffsetAccessible(): TrinaryLogic
    {
        if ($this->isOffsetAccessible === null) {
            throw new ShouldNotHappenException();
        }
        return $this->isOffsetAccessible;
    }
    public function getHasOffsetValue(): TrinaryLogic
    {
        if ($this->hasOffsetValue === null) {
            throw new ShouldNotHappenException();
        }
        return $this->hasOffsetValue;
    }
    public function hasExpressionTypeOfExpr(): bool
    {
        return $this->hasExpressionTypeOfExpr;
    }
    public function getVarType(): Type
    {
        if ($this->varType === null) {
            throw new ShouldNotHappenException();
        }
        return $this->varType;
    }
    public function getDimType(): Type
    {
        if ($this->dimType === null) {
            throw new ShouldNotHappenException();
        }
        return $this->dimType;
    }
    public function getPropertyReflection(): ?FoundPropertyReflection
    {
        return $this->propertyReflection;
    }
    /**
     * @return Expr\PropertyFetch|Expr\StaticPropertyFetch
     */
    public function getPropertyFetch(): Expr
    {
        if (!$this->propertyFetch instanceof Expr\PropertyFetch && !$this->propertyFetch instanceof Expr\StaticPropertyFetch) {
            throw new ShouldNotHappenException();
        }
        return $this->propertyFetch;
    }
    public function isReflectionNative(): bool
    {
        return $this->reflectionNative;
    }
    public function hasNativeType(): bool
    {
        return $this->hasNativeType;
    }
    public function isVirtual(): TrinaryLogic
    {
        if ($this->isVirtual === null) {
            throw new ShouldNotHappenException();
        }
        return $this->isVirtual;
    }
    public function getNativeType(): Type
    {
        if ($this->nativeType === null) {
            throw new ShouldNotHappenException();
        }
        return $this->nativeType;
    }
    public function hasExpressionTypeOfFetch(): bool
    {
        return $this->hasExpressionTypeOfFetch;
    }
    public function isInitializedThisProperty(): bool
    {
        return $this->initializedThisProperty;
    }
    public function nativeReflectionExists(): bool
    {
        return $this->nativeReflectionExists;
    }
    public function nativeIsPromoted(): bool
    {
        return $this->nativeIsPromoted;
    }
    public function nativeIsReadOnly(): bool
    {
        return $this->nativeIsReadOnly;
    }
    public function nativeIsHooked(): bool
    {
        return $this->nativeIsHooked;
    }
    public function nativeHasDefaultValue(): bool
    {
        return $this->nativeHasDefaultValue;
    }
    public function getLeafExpr(): Expr
    {
        if ($this->leafExpr === null) {
            throw new ShouldNotHappenException();
        }
        return $this->leafExpr;
    }
    public function leafIsNullsafePropertyFetch(): bool
    {
        return $this->leafIsNullsafePropertyFetch;
    }
}
