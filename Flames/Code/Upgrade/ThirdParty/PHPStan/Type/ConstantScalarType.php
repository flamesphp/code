<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * A type whose value is known at analysis time — a compile-time constant scalar.
 *
 * Implemented by ConstantIntegerType, ConstantFloatType, ConstantStringType,
 * ConstantBooleanType, and NullType.
 *
 * Use Type::isConstantValue() to check if a type is constant without instanceof,
 * and Type::getConstantScalarTypes() to extract constant types from unions.
 *
 * @api
 */
#[\PHPStan\Type\InstanceofDeprecated('Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()')]
#[ReferencedByTurboExtension('constantScalarType')]
interface ConstantScalarType extends \PHPStan\Type\Type
{
    /** @return int|float|string|bool|null */
    public function getValue();
}
