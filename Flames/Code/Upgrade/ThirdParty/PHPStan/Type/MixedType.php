<?php

declare (strict_types=1);
namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\Dummy\DummyClassConstantReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\SubstractableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use function get_class;
use function sprintf;
/** @api */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/MixedType.cpp')]
class MixedType implements \PHPStan\Type\CompoundType, \PHPStan\Type\SubtractableType
{
    private bool $isExplicitMixed;
    use NonGenericTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonGeneralizableTypeTrait;
    use SubstractableTypeTrait;
    private ?\PHPStan\Type\Type $subtractedType;
    /** @api */
    public function __construct(bool $isExplicitMixed = \false, ?\PHPStan\Type\Type $subtractedType = null)
    {
        $this->isExplicitMixed = $isExplicitMixed;
        if ($subtractedType instanceof \PHPStan\Type\NeverType) {
            $subtractedType = null;
        }
        $this->subtractedType = $subtractedType;
    }
    public function getReferencedClasses(): array
    {
        return [];
    }
    public function getObjectClassNames(): array
    {
        return [];
    }
    public function getObjectClassReflections(): array
    {
        return [];
    }
    public function getArrays(): array
    {
        return [];
    }
    public function getConstantArrays(): array
    {
        return [];
    }
    public function getConstantStrings(): array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        return \PHPStan\Type\AcceptsResult::createYes();
    }
    public function isSuperTypeOfMixed(\PHPStan\Type\MixedType $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($this->subtractedType === null) {
            if ($this->isExplicitMixed) {
                if ($type->isExplicitMixed) {
                    return \PHPStan\Type\IsSuperTypeOfResult::createYes();
                }
                return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
            }
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type->subtractedType === null) {
            return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
        }
        $isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
        if ($isSuperType->yes()) {
            if ($this->isExplicitMixed) {
                if ($type->isExplicitMixed) {
                    return \PHPStan\Type\IsSuperTypeOfResult::createYes();
                }
                return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
            }
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($this->subtractedType === null || $type instanceof \PHPStan\Type\NeverType) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type instanceof self) {
            if ($type->subtractedType === null) {
                return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
            }
            $isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
            if ($isSuperType->yes()) {
                return $isSuperType;
            }
            return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
        }
        $result = $this->subtractedType->isSuperTypeOf($type)->negate();
        if ($result->no()) {
            return \PHPStan\Type\IsSuperTypeOfResult::createNo([sprintf('Type %s has already been eliminated from %s.', $this->subtractedType->describe(\PHPStan\Type\VerbosityLevel::precise()), $this->describe(\PHPStan\Type\VerbosityLevel::typeOnly()))]);
        }
        return $result;
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        if ($this->subtractedType !== null) {
            return new self($this->isExplicitMixed, \PHPStan\Type\TypeCombinator::remove($this->subtractedType, new ConstantArrayType([], [])));
        }
        return $this;
    }
    public function getKeysArrayFiltered(\PHPStan\Type\Type $filterValueType, TrinaryLogic $strict): \PHPStan\Type\Type
    {
        return $this->getKeysArray();
    }
    public function getKeysArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])), new AccessoryArrayListType()]);
    }
    public function getValuesArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType($this->isExplicitMixed)), new AccessoryArrayListType()]);
    }
    public function chunkArray(\PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType($this->isExplicitMixed)), new AccessoryArrayListType()]);
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType($this->getIterableValueType()->toArrayKey(), $valueType);
    }
    public function flipArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function popArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function reverseArray(TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function searchArray(\PHPStan\Type\Type $needleType, ?TrinaryLogic $strict = null): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new ConstantBooleanType(\false)]);
    }
    public function shiftArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function shuffleArray(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType($this->isExplicitMixed)), new AccessoryArrayListType()]);
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function spliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, \PHPStan\Type\Type $replacementType): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function truncateListToSize(\PHPStan\Type\Type $sizeType): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return $this;
    }
    public function makeListMaybe(): \PHPStan\Type\Type
    {
        // `mixed` doesn't track list-ness; nothing to weaken.
        return $this;
    }
    public function mapValueType(callable $cb): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), $cb(new \PHPStan\Type\MixedType($this->isExplicitMixed)));
    }
    public function mapKeyType(callable $cb): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType($cb(new \PHPStan\Type\MixedType($this->isExplicitMixed)), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function makeAllArrayKeysOptional(): \PHPStan\Type\Type
    {
        // `mixed` is already arbitrary; nothing to weaken.
        return $this;
    }
    public function changeKeyCaseArray(?int $case): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function filterArrayRemovingFalsey(): \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function isCallable(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\CallableType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function getEnumCases(): array
    {
        return [];
    }
    public function getEnumCaseObject(): ?EnumCaseObjectType
    {
        return null;
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        return [new TrivialParametersAcceptor()];
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        if (get_class($type) !== static::class) {
            return \false;
        }
        if ($this->subtractedType === null) {
            if ($type->subtractedType === null) {
                return \true;
            }
            return \false;
        }
        if ($type->subtractedType === null) {
            return \false;
        }
        return $this->subtractedType->equals($type->subtractedType);
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($otherType instanceof self && !$otherType instanceof TemplateMixedType) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($this->subtractedType !== null) {
            $isSuperType = $this->subtractedType->isSuperTypeOf($otherType);
            if ($isSuperType->yes()) {
                return \PHPStan\Type\IsSuperTypeOfResult::createNo();
            }
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        $isSuperType = $this->isSuperTypeOf($acceptingType)->toAcceptsResult();
        if ($isSuperType->no()) {
            return $isSuperType;
        }
        return \PHPStan\Type\AcceptsResult::createYes();
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName): \PHPStan\Type\Type
    {
        return new self();
    }
    public function isObject(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ObjectWithoutClassType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function getClassStringType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ClassStringType();
    }
    public function isEnum(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ObjectWithoutClassType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function canAccessProperties(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasProperty(string $propertyName): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $property = new DummyPropertyReflection($propertyName);
        return new CallbackUnresolvedPropertyPrototypeReflection($property, $property->getDeclaringClass(), \false, static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type);
    }
    public function hasInstanceProperty(string $propertyName): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $property = new DummyPropertyReflection($propertyName);
        return new CallbackUnresolvedPropertyPrototypeReflection($property, $property->getDeclaringClass(), \false, static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type);
    }
    public function hasStaticProperty(string $propertyName): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedStaticPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $property = new DummyPropertyReflection($propertyName);
        return new CallbackUnresolvedPropertyPrototypeReflection($property, $property->getDeclaringClass(), \false, static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type);
    }
    public function canCallMethods(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasMethod(string $methodName): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        $method = new DummyMethodReflection($methodName);
        return new CallbackUnresolvedMethodPrototypeReflection($method, $method->getDeclaringClass(), \false, static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type);
    }
    public function canAccessConstants(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasConstant(string $constantName): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getConstant(string $constantName): ClassConstantReflection
    {
        return new DummyClassConstantReflection($constantName);
    }
    public function isCloneable(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return $level->handle(static fn(): string => 'mixed', static fn(): string => 'mixed', fn(): string => 'mixed' . $this->describeSubtractedType($this->subtractedType, $level), function () use ($level): string {
            $description = 'mixed' . $this->describeSubtractedType($this->subtractedType, $level);
            if ($this->isExplicitMixed) {
                $description .= '=explicit';
            } else {
                $description .= '=implicit';
            }
            return $description;
        });
    }
    public function toBoolean(): \PHPStan\Type\BooleanType
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(\PHPStan\Type\StaticTypeFactory::falsey())->yes()) {
                return new ConstantBooleanType(\true);
            }
        }
        return new \PHPStan\Type\BooleanType();
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union($this->toInteger(), $this->toFloat());
    }
    public function toBitwiseNotType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toGetClassResultType(): \PHPStan\Type\Type
    {
        $isObject = $this->isObject();
        if ($isObject->no()) {
            return new ConstantBooleanType(\false);
        }
        $classString = $this->getClassStringType();
        if ($isObject->yes()) {
            return $classString;
        }
        return new \PHPStan\Type\UnionType([$classString, new ConstantBooleanType(\false)]);
    }
    public function toClassConstantType(ReflectionProvider $reflectionProvider): \PHPStan\Type\Type
    {
        // `mixed::class` is undefined — the original `TypeTraverser` cb fell
        // through to `ErrorType` for any leaf that wasn't a definite object.
        return new \PHPStan\Type\ErrorType();
    }
    public function toObjectTypeForInstanceofCheck(): \PHPStan\Type\ClassNameToObjectTypeResult
    {
        return new \PHPStan\Type\ClassNameToObjectTypeResult(new \PHPStan\Type\MixedType(), \false);
    }
    public function toObjectTypeForIsACheck(\PHPStan\Type\Type $objectOrClassType, bool $allowString, bool $allowSameClass): \PHPStan\Type\ClassNameToObjectTypeResult
    {
        if ($allowString) {
            return new \PHPStan\Type\ClassNameToObjectTypeResult(new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectWithoutClassType(), new \PHPStan\Type\ClassStringType()]), \false);
        }
        return new \PHPStan\Type\ClassNameToObjectTypeResult(new \PHPStan\Type\ObjectWithoutClassType(), \false);
    }
    public function toAbsoluteNumber(): \PHPStan\Type\Type
    {
        return $this->toNumber()->toAbsoluteNumber();
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        $castsToZero = new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantIntegerType(0), new ConstantArrayType([], []), new \PHPStan\Type\StringType(), new \PHPStan\Type\FloatType()]);
        if ($this->subtractedType !== null && $this->subtractedType->isSuperTypeOf($castsToZero)->yes()) {
            return new \PHPStan\Type\UnionType([\PHPStan\Type\IntegerRangeType::fromInterval(null, -1), \PHPStan\Type\IntegerRangeType::fromInterval(1, null)]);
        }
        return new \PHPStan\Type\IntegerType();
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\FloatType();
    }
    public function toString(): \PHPStan\Type\Type
    {
        if ($this->subtractedType !== null) {
            $castsToEmptyString = new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantStringType('')]);
            if ($this->subtractedType->isSuperTypeOf($castsToEmptyString)->yes()) {
                $accessories = [new \PHPStan\Type\StringType(), new AccessoryNonEmptyStringType()];
                $castsToZeroString = new \PHPStan\Type\UnionType([new ConstantFloatType(0.0), new ConstantStringType('0'), new ConstantIntegerType(0)]);
                if ($this->subtractedType->isSuperTypeOf($castsToZeroString)->yes()) {
                    $accessories[] = new AccessoryNonFalsyStringType();
                }
                return new \PHPStan\Type\IntersectionType($accessories);
            }
        }
        return new \PHPStan\Type\StringType();
    }
    public function toArray(): \PHPStan\Type\Type
    {
        $mixed = new self($this->isExplicitMixed);
        return new \PHPStan\Type\ArrayType($mixed, $mixed);
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return $this;
    }
    public function isIterable(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isIterableAtLeastOnce(): TrinaryLogic
    {
        return $this->isIterable();
    }
    public function getArraySize(): \PHPStan\Type\Type
    {
        if ($this->isIterable()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\IntegerRangeType::fromInterval(0, null);
    }
    public function getIterableKeyType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getFirstIterableKeyType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getLastIterableKeyType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getIterableValueType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getFirstIterableValueType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getLastIterableValueType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function isOffsetAccessible(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $offsetAccessibles = new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\ObjectType(ArrayAccess::class)]);
            if ($this->subtractedType->isSuperTypeOf($offsetAccessibles)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ObjectWithoutClassType())->yes()) {
                return TrinaryLogic::createYes();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): TrinaryLogic
    {
        if ($this->isOffsetAccessible()->no()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function isExplicitMixed(): bool
    {
        return $this->isExplicitMixed;
    }
    public function subtract(\PHPStan\Type\Type $type): \PHPStan\Type\Type
    {
        if ($type instanceof self && !$type instanceof TemplateType) {
            return new \PHPStan\Type\NeverType();
        }
        if ($this->subtractedType !== null) {
            $type = \PHPStan\Type\TypeCombinator::union($this->subtractedType, $type);
        }
        return new self($this->isExplicitMixed, $type);
    }
    public function getTypeWithoutSubtractedType(): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType): \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed, $subtractedType);
    }
    public function getSubtractedType(): ?\PHPStan\Type\Type
    {
        return $this->subtractedType;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function isArray(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isConstantArray(): TrinaryLogic
    {
        return $this->isArray();
    }
    public function isOversizedArray(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $oversizedArray = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new OversizedArrayType()]);
            if ($this->subtractedType->isSuperTypeOf($oversizedArray)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isList(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $list = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\MixedType()), new AccessoryArrayListType()]);
            if ($this->subtractedType->isSuperTypeOf($list)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNull(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\NullType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isConstantValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantScalarValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstantScalarTypes(): array
    {
        return [];
    }
    public function getConstantScalarValues(): array
    {
        return [];
    }
    public function isTrue(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(\true))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isFalse(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(\false))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isBoolean(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\BooleanType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isFloat(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\FloatType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isInteger(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\IntegerType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\StringType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNumericString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $numericString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryNumericStringType()]);
            if ($this->subtractedType->isSuperTypeOf($numericString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isDecimalIntegerString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $decimalIntegerString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryDecimalIntegerStringType()]);
            if ($this->subtractedType->isSuperTypeOf($decimalIntegerString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $nonEmptyString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryNonEmptyStringType()]);
            if ($this->subtractedType->isSuperTypeOf($nonEmptyString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $nonFalsyString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryNonFalsyStringType()]);
            if ($this->subtractedType->isSuperTypeOf($nonFalsyString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isLiteralString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $literalString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryLiteralStringType()]);
            if ($this->subtractedType->isSuperTypeOf($literalString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $lowercaseString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryLowercaseStringType()]);
            if ($this->subtractedType->isSuperTypeOf($lowercaseString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isUppercaseString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $uppercaseString = new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryUppercaseStringType()]);
            if ($this->subtractedType->isSuperTypeOf($uppercaseString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isClassString(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\StringType())->yes()) {
                return TrinaryLogic::createNo();
            }
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ClassStringType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function getClassStringObjectType(): \PHPStan\Type\Type
    {
        if (!$this->isClassString()->no()) {
            return new \PHPStan\Type\ObjectWithoutClassType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType(): \PHPStan\Type\Type
    {
        $objectOrClass = new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectWithoutClassType(), new \PHPStan\Type\ClassStringType()]);
        if (!$this->isSuperTypeOf($objectOrClass)->no()) {
            return new \PHPStan\Type\ObjectWithoutClassType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\VoidType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isScalar(): TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\UnionType([new \PHPStan\Type\BooleanType(), new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove): ?\PHPStan\Type\Type
    {
        // mixed is the top type, so removal is exactly the subtraction - also
        // when an earlier subtraction already lowered isSuperTypeOf() from yes
        // to maybe, which used to give up and remove nothing at all
        if ($this->isSuperTypeOf($typeToRemove)->no()) {
            return null;
        }
        return $this->subtract($typeToRemove);
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }
    public function hasTemplateOrLateResolvableType(): bool
    {
        return \false;
    }
}
