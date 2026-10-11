<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use UnitEnum;
use function array_is_list;
use function count;
use function function_exists;
use function get_class;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ConstantTypeHelper.cpp')]
final class ConstantTypeHelper
{
    /**
     * @param mixed $value
     */
    public static function getTypeFromValue($value): \PHPStan\Type\Type
    {
        if (is_int($value)) {
            return new ConstantIntegerType($value);
        } elseif (is_float($value)) {
            return new ConstantFloatType($value);
        } elseif (is_bool($value)) {
            return new ConstantBooleanType($value);
        } elseif ($value === null) {
            return new \PHPStan\Type\NullType();
        } elseif (is_string($value)) {
            return new ConstantStringType($value);
        } elseif (is_array($value)) {
            if (count($value) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
                return self::getOversizedArrayType($value);
            }
            $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
            foreach ($value as $k => $v) {
                $arrayBuilder->setOffsetValueType(self::getTypeFromValue($k), self::getTypeFromValue($v));
            }
            return $arrayBuilder->getArray();
        } elseif (is_object($value)) {
            $class = get_class($value);
            /** phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName */
            if (function_exists('enum_exists') && \enum_exists($class)) {
                /** @var UnitEnum $value */
                return new EnumCaseObjectType($class, $value->name);
            }
            /** phpcs:enable */
            return new \PHPStan\Type\ObjectType(get_class($value));
        }
        return new \PHPStan\Type\MixedType();
    }
    /**
     * Generalizes keys and values like OversizedArrayBuilder does for array literals,
     * so that the result does not carry unions of thousands of constant types.
     *
     * @param non-empty-array<mixed> $value
     */
    private static function getOversizedArrayType(array $value): \PHPStan\Type\Type
    {
        $precision = \PHPStan\Type\GeneralizePrecision::moreSpecific();
        $keyType = new \PHPStan\Type\NeverType();
        $valueType = new \PHPStan\Type\NeverType();
        foreach ($value as $k => $v) {
            $keyType = self::unionGeneralized($keyType, self::getTypeFromValue($k), $precision);
            $valueType = self::unionGeneralized($valueType, self::getTypeFromValue($v), $precision);
        }
        $accessories = [new NonEmptyArrayType(), new OversizedArrayType()];
        if (array_is_list($value)) {
            $accessories[] = new AccessoryArrayListType();
        }
        return \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\ArrayType($keyType, $valueType), ...$accessories);
    }
    /** Skips the union when the generalized type is already the accumulated one, which is the common case. */
    private static function unionGeneralized(\PHPStan\Type\Type $accumulated, \PHPStan\Type\Type $type, \PHPStan\Type\GeneralizePrecision $precision): \PHPStan\Type\Type
    {
        $generalized = $type->generalize($precision);
        if ($generalized->equals($accumulated)) {
            return $accumulated;
        }
        return \PHPStan\Type\TypeCombinator::union($accumulated, $generalized);
    }
}
