<?php

declare (strict_types=1);
namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/StaticTypeFactory.cpp')]
final class StaticTypeFactory
{
    public static function falsey(): \PHPStan\Type\Type
    {
        static $falsey;
        if ($falsey === null) {
            $falsey = \PHPStan\Type\TypeCombinator::union(new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType(''), new ConstantStringType('0'), new ConstantArrayType([], []));
        }
        return $falsey;
    }
    public static function truthy(): \PHPStan\Type\Type
    {
        static $truthy;
        if ($truthy === null) {
            $truthy = new \PHPStan\Type\MixedType(\false, self::falsey());
        }
        return $truthy;
    }
    public static function argv(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\ArrayType(\PHPStan\Type\IntegerRangeType::createAllGreaterThanOrEqualTo(0), new \PHPStan\Type\StringType()), new NonEmptyArrayType(), new AccessoryArrayListType()]);
    }
    public static function argc(): \PHPStan\Type\Type
    {
        return \PHPStan\Type\IntegerRangeType::fromInterval(1, null);
    }
    public static function generalOffsetAccessibleType(): \PHPStan\Type\Type
    {
        static $generalOffsetAccessible;
        if ($generalOffsetAccessible === null) {
            $generalOffsetAccessible = \PHPStan\Type\TypeCombinator::union(new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\ObjectType(ArrayAccess::class), new \PHPStan\Type\NullType());
        }
        return $generalOffsetAccessible;
    }
    public static function intOffsetAccessibleType(): \PHPStan\Type\Type
    {
        static $intOffsetAccessible;
        if ($intOffsetAccessible === null) {
            $intOffsetAccessible = \PHPStan\Type\TypeCombinator::union(self::generalOffsetAccessibleType(), new \PHPStan\Type\StringType());
        }
        return $intOffsetAccessible;
    }
}
