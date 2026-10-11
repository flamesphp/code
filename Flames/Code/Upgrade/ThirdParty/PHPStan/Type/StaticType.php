<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function get_class;
use function sprintf;
/** @api */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/StaticType.cpp')]
class StaticType implements \PHPStan\Type\TypeWithClassName, \PHPStan\Type\SubtractableType
{
    private ClassReflection $classReflection;
    use NonGenericTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGeneralizableTypeTrait;
    private ?\PHPStan\Type\Type $subtractedType;
    private ?\PHPStan\Type\ObjectType $staticObjectType = null;
    private string $baseClass;
    /** @var array<string, ExtendedMethodReflection> */
    private array $methodCache = [];
    /**
     * @api
     */
    public function __construct(ClassReflection $classReflection, ?\PHPStan\Type\Type $subtractedType = null)
    {
        $this->classReflection = $classReflection;
        if ($subtractedType instanceof \PHPStan\Type\NeverType) {
            $subtractedType = null;
        }
        $this->subtractedType = $subtractedType;
        $this->baseClass = $classReflection->getName();
    }
    public function getClassName(): string
    {
        return $this->baseClass;
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getAncestorWithClassName(string $className): ?\PHPStan\Type\TypeWithClassName
    {
        $ancestor = $this->getStaticObjectType()->getAncestorWithClassName($className);
        if ($ancestor === null) {
            return null;
        }
        $classReflection = $ancestor->getClassReflection();
        if ($classReflection !== null) {
            return $this->changeBaseClass($classReflection);
        }
        return null;
    }
    public function getStaticObjectType(): \PHPStan\Type\ObjectType
    {
        if ($this->staticObjectType === null) {
            if ($this->classReflection->isGeneric()) {
                $typeMap = $this->classReflection->getActiveTemplateTypeMap()->map(static fn(string $name, \PHPStan\Type\Type $type): \PHPStan\Type\Type => TemplateTypeHelper::toArgument($type));
                $varianceMap = $this->classReflection->getCallSiteVarianceMap();
                return $this->staticObjectType = new GenericObjectType($this->classReflection->getName(), $this->classReflection->typeMapToList($typeMap), $this->subtractedType, \null, $this->classReflection->varianceMapToList($varianceMap));
            }
            return $this->staticObjectType = new \PHPStan\Type\ObjectType($this->classReflection->getName(), $this->subtractedType, $this->classReflection);
        }
        return $this->staticObjectType;
    }
    public function getReferencedClasses(): array
    {
        return $this->getStaticObjectType()->getReferencedClasses();
    }
    public function getObjectClassNames(): array
    {
        return $this->getStaticObjectType()->getObjectClassNames();
    }
    public function getObjectClassReflections(): array
    {
        return $this->getStaticObjectType()->getObjectClassReflections();
    }
    public function getArrays(): array
    {
        return $this->getStaticObjectType()->getArrays();
    }
    public function getConstantArrays(): array
    {
        return $this->getStaticObjectType()->getConstantArrays();
    }
    public function getConstantStrings(): array
    {
        return $this->getStaticObjectType()->getConstantStrings();
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        if (!$type instanceof static) {
            return \PHPStan\Type\AcceptsResult::createNo();
        }
        return $this->getStaticObjectType()->accepts($type->getStaticObjectType(), $strictTypes);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->getStaticObjectType()->isSuperTypeOf($type);
        }
        if ($type instanceof \PHPStan\Type\ObjectWithoutClassType) {
            return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
        }
        if ($type instanceof \PHPStan\Type\ObjectType) {
            $result = $this->getStaticObjectType()->isSuperTypeOf($type);
            if ($result->yes()) {
                $classReflection = $type->getClassReflection();
                if ($classReflection !== null && $classReflection->isFinal()) {
                    return $result;
                }
            }
            return $result->and(\PHPStan\Type\IsSuperTypeOfResult::createMaybe());
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        if (get_class($type) !== static::class) {
            return \false;
        }
        return $this->getStaticObjectType()->equals($type->getStaticObjectType());
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return sprintf('static(%s)', $this->getStaticObjectType()->describe($level));
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getTemplateType($ancestorClassName, $templateTypeName);
    }
    public function isObject(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isObject();
    }
    public function getClassStringType(): \PHPStan\Type\Type
    {
        return new GenericClassStringType($this);
    }
    public function isEnum(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isEnum();
    }
    public function canAccessProperties(): TrinaryLogic
    {
        return $this->getStaticObjectType()->canAccessProperties();
    }
    public function hasProperty(string $propertyName): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasProperty($propertyName);
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $staticObject = $this->getStaticObjectType();
        $nakedProperty = $staticObject->getUnresolvedPropertyPrototype($propertyName, $scope)->getNakedProperty();
        $ancestor = $this->getAncestorWithClassName($nakedProperty->getDeclaringClass()->getName());
        $classReflection = null;
        if ($ancestor !== null) {
            $classReflection = $ancestor->getClassReflection();
        }
        if ($classReflection === null) {
            $classReflection = $nakedProperty->getDeclaringClass();
        }
        return new CallbackUnresolvedPropertyPrototypeReflection($nakedProperty, $classReflection, \false, fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $this->transformStaticType($type, $scope));
    }
    public function hasInstanceProperty(string $propertyName): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasInstanceProperty($propertyName);
    }
    public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $staticObject = $this->getStaticObjectType();
        $nakedProperty = $staticObject->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->getNakedProperty();
        $ancestor = $this->getAncestorWithClassName($nakedProperty->getDeclaringClass()->getName());
        $classReflection = null;
        if ($ancestor !== null) {
            $classReflection = $ancestor->getClassReflection();
        }
        if ($classReflection === null) {
            $classReflection = $nakedProperty->getDeclaringClass();
        }
        return new CallbackUnresolvedPropertyPrototypeReflection($nakedProperty, $classReflection, \false, fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $this->transformStaticType($type, $scope));
    }
    public function hasStaticProperty(string $propertyName): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasStaticProperty($propertyName);
    }
    public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedStaticPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $staticObject = $this->getStaticObjectType();
        $nakedProperty = $staticObject->getUnresolvedStaticPropertyPrototype($propertyName, $scope)->getNakedProperty();
        $ancestor = $this->getAncestorWithClassName($nakedProperty->getDeclaringClass()->getName());
        $classReflection = null;
        if ($ancestor !== null) {
            $classReflection = $ancestor->getClassReflection();
        }
        if ($classReflection === null) {
            $classReflection = $nakedProperty->getDeclaringClass();
        }
        return new CallbackUnresolvedPropertyPrototypeReflection($nakedProperty, $classReflection, \false, fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $this->transformStaticType($type, $scope));
    }
    public function canCallMethods(): TrinaryLogic
    {
        return $this->getStaticObjectType()->canCallMethods();
    }
    public function hasMethod(string $methodName): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasMethod($methodName);
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        $key = $methodName;
        if ($scope->isInClass()) {
            $key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
        }
        return $this->methodCache[$key] ??= $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        $staticObject = $this->getStaticObjectType();
        $nakedMethod = $staticObject->getUnresolvedMethodPrototype($methodName, $scope)->getNakedMethod();
        $ancestor = $this->getAncestorWithClassName($nakedMethod->getDeclaringClass()->getName());
        $classReflection = null;
        if ($ancestor !== null) {
            $classReflection = $ancestor->getClassReflection();
        }
        if ($classReflection === null) {
            $classReflection = $nakedMethod->getDeclaringClass();
        }
        return new CallbackUnresolvedMethodPrototypeReflection($nakedMethod, $classReflection, \false, fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $this->transformStaticType($type, $scope));
    }
    private function transformStaticType(\PHPStan\Type\Type $type, ClassMemberAccessAnswerer $scope): \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeTraverser::map($type, function (\PHPStan\Type\Type $type, callable $traverse) use ($scope): \PHPStan\Type\Type {
            if ($type instanceof \PHPStan\Type\StaticType) {
                $classReflection = $this->classReflection;
                $isFinal = \false;
                if ($scope->isInClass()) {
                    $classReflection = $scope->getClassReflection();
                    $isFinal = $classReflection->isFinal();
                }
                $type = $type->changeBaseClass($classReflection);
                // When calling a method on a `static` type (not `$this`),
                // `$this` return type should be downgraded to `static`
                // because we can't guarantee the exact instance.
                if ($type instanceof \PHPStan\Type\ThisType && !$this instanceof \PHPStan\Type\ThisType) {
                    $type = new self($type->getClassReflection(), $type->getSubtractedType());
                }
                if ($this->getSubtractedType() !== null) {
                    $type = $type->subtract($this->getSubtractedType());
                    if (!$type instanceof \PHPStan\Type\StaticType) {
                        return $traverse($type);
                    }
                }
                if (!$isFinal || $type instanceof \PHPStan\Type\ThisType) {
                    return \PHPStan\Type\RecursionGuard::run($type, static fn() => $traverse($type));
                }
                return $traverse($type->getStaticObjectType());
            }
            return $traverse($type);
        });
    }
    public function canAccessConstants(): TrinaryLogic
    {
        return $this->getStaticObjectType()->canAccessConstants();
    }
    public function hasConstant(string $constantName): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasConstant($constantName);
    }
    public function getConstant(string $constantName): ClassConstantReflection
    {
        return $this->getStaticObjectType()->getConstant($constantName);
    }
    public function changeBaseClass(ClassReflection $classReflection): self
    {
        return new self($classReflection, $this->subtractedType);
    }
    public function isIterable(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isIterable();
    }
    public function isIterableAtLeastOnce(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isIterableAtLeastOnce();
    }
    public function getArraySize(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getArraySize();
    }
    public function getIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableKeyType();
    }
    public function getFirstIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableKeyType();
    }
    public function getLastIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableKeyType();
    }
    public function getIterableValueType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableValueType();
    }
    public function getFirstIterableValueType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableValueType();
    }
    public function getLastIterableValueType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getIterableValueType();
    }
    public function isOffsetAccessible(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isOffsetAccessible();
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isOffsetAccessLegal();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): TrinaryLogic
    {
        return $this->getStaticObjectType()->hasOffsetValueType($offsetType);
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getOffsetValueType($offsetType);
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->setOffsetValueType($offsetType, $valueType, $unionValues);
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->setExistingOffsetValueType($offsetType, $valueType);
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->unsetOffset($offsetType);
    }
    public function getKeysArrayFiltered(\PHPStan\Type\Type $filterValueType, TrinaryLogic $strict): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getKeysArrayFiltered($filterValueType, $strict);
    }
    public function getKeysArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getKeysArray();
    }
    public function getValuesArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getValuesArray();
    }
    public function chunkArray(\PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->chunkArray($lengthType, $preserveKeys);
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->fillKeysArray($valueType);
    }
    public function flipArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->flipArray();
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->intersectKeyArray($otherArraysType);
    }
    public function popArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->popArray();
    }
    public function reverseArray(TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->reverseArray($preserveKeys);
    }
    public function searchArray(\PHPStan\Type\Type $needleType, ?TrinaryLogic $strict = null): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->searchArray($needleType, $strict);
    }
    public function shiftArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->shiftArray();
    }
    public function shuffleArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->shuffleArray();
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->sliceArray($offsetType, $lengthType, $preserveKeys);
    }
    public function spliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, \PHPStan\Type\Type $replacementType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->spliceArray($offsetType, $lengthType, $replacementType);
    }
    public function truncateListToSize(\PHPStan\Type\Type $sizeType): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->truncateListToSize($sizeType);
    }
    public function makeListMaybe(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->makeListMaybe();
    }
    public function mapValueType(callable $cb): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->mapValueType($cb);
    }
    public function mapKeyType(callable $cb): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->mapKeyType($cb);
    }
    public function makeAllArrayKeysOptional(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->makeAllArrayKeysOptional();
    }
    public function changeKeyCaseArray(?int $case): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->changeKeyCaseArray($case);
    }
    public function filterArrayRemovingFalsey(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->filterArrayRemovingFalsey();
    }
    public function isCallable(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isCallable();
    }
    public function getEnumCases(): array
    {
        return $this->getStaticObjectType()->getEnumCases();
    }
    public function getEnumCaseObject(): ?EnumCaseObjectType
    {
        return $this->getStaticObjectType()->getEnumCaseObject();
    }
    public function isArray(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isArray();
    }
    public function isConstantArray(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isConstantArray();
    }
    public function isOversizedArray(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isOversizedArray();
    }
    public function isList(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isList();
    }
    public function isNull(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isNull();
    }
    public function isConstantValue(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isConstantValue();
    }
    public function isConstantScalarValue(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isConstantScalarValue();
    }
    public function getConstantScalarTypes(): array
    {
        return $this->getStaticObjectType()->getConstantScalarTypes();
    }
    public function getConstantScalarValues(): array
    {
        return $this->getStaticObjectType()->getConstantScalarValues();
    }
    public function isTrue(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isTrue();
    }
    public function isFalse(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isFalse();
    }
    public function isBoolean(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isBoolean();
    }
    public function isFloat(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isFloat();
    }
    public function isInteger(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isInteger();
    }
    public function isString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isString();
    }
    public function isNumericString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isNumericString();
    }
    public function isDecimalIntegerString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isDecimalIntegerString();
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isNonEmptyString();
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isNonFalsyString();
    }
    public function isLiteralString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isLiteralString();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isLowercaseString();
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isUppercaseString();
    }
    public function isClassString(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isClassString();
    }
    public function getClassStringObjectType(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->getClassStringObjectType();
    }
    public function getObjectTypeOrClassStringObjectType(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function isVoid(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isVoid();
    }
    public function isScalar(): TrinaryLogic
    {
        return $this->getStaticObjectType()->isScalar();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        return $this->getStaticObjectType()->getCallableParametersAcceptors($scope);
    }
    public function isCloneable(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toBitwiseNotType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toGetClassResultType(): \PHPStan\Type\Type
    {
        // Preserve static binding (`class-string<static>` /
        // `class-string<$this>`) by going through `getClassStringType()`
        // directly instead of delegating to the underlying object type,
        // which would resolve `static` away.
        return $this->getClassStringType();
    }
    public function toClassConstantType(ReflectionProvider $reflectionProvider): \PHPStan\Type\Type
    {
        // Like `toGetClassResultType()`, project through this `StaticType`'s
        // own `getClassStringType()` so that `static::class` reads as
        // `class-string<static>` rather than the underlying class.
        return new \PHPStan\Type\IntersectionType([$this->getClassStringType(), new AccessoryLiteralStringType()]);
    }
    public function toObjectTypeForInstanceofCheck(): \PHPStan\Type\ClassNameToObjectTypeResult
    {
        return new \PHPStan\Type\ClassNameToObjectTypeResult($this, \true);
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
        return new \PHPStan\Type\ErrorType();
    }
    public function toString(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->toString();
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->toArray();
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->toArrayKey();
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->toCoercedArgumentType($strictTypes);
    }
    public function toBoolean(): \PHPStan\Type\BooleanType
    {
        return $this->getStaticObjectType()->toBoolean();
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;
        if ($subtractedType !== $this->subtractedType) {
            return new self($this->classReflection, $subtractedType);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if ($this->subtractedType === null) {
            return $this;
        }
        return new self($this->classReflection);
    }
    public function subtract(\PHPStan\Type\Type $type): \PHPStan\Type\Type
    {
        if ($this->subtractedType !== null) {
            $type = \PHPStan\Type\TypeCombinator::union($this->subtractedType, $type);
        }
        return $this->changeSubtractedType($type);
    }
    public function getTypeWithoutSubtractedType(): \PHPStan\Type\Type
    {
        return $this->changeSubtractedType(null);
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType): \PHPStan\Type\Type
    {
        if ($subtractedType !== null) {
            $classReflection = $this->getClassReflection();
            if ($classReflection->getAllowedSubTypes() !== null) {
                $objectType = $this->getStaticObjectType()->changeSubtractedType($subtractedType);
                if ($objectType instanceof \PHPStan\Type\NeverType) {
                    return $objectType;
                }
                if ($objectType instanceof \PHPStan\Type\ObjectType && $objectType->getSubtractedType() !== null) {
                    return new self($classReflection, $objectType->getSubtractedType());
                }
                return \PHPStan\Type\TypeCombinator::intersect($this, $objectType);
            }
        }
        return new self($this->classReflection, $subtractedType);
    }
    public function getSubtractedType(): ?\PHPStan\Type\Type
    {
        return $this->subtractedType;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove): ?\PHPStan\Type\Type
    {
        if ($this->getStaticObjectType()->isSuperTypeOf($typeToRemove)->yes()) {
            return $this->subtract($typeToRemove);
        }
        return null;
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return $this->getStaticObjectType()->exponentiate($exponent);
    }
    public function getFiniteTypes(): array
    {
        return $this->getStaticObjectType()->getFiniteTypes();
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('static');
    }
    public function hasTemplateOrLateResolvableType(): bool
    {
        return $this->getStaticObjectType()->hasTemplateOrLateResolvableType();
    }
}
