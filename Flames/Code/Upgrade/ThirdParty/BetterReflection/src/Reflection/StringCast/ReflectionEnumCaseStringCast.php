<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use function gettype;
use function sprintf;
/** @internal */
final class ReflectionEnumCaseStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionEnumCase $enumCaseReflection, bool $indentDocComment = \true): string
    {
        $enumReflection = $enumCaseReflection->enum;
        $value = $enumReflection->backingType !== null ? $enumCaseReflection->getValue() : 'Object';
        $type = $enumReflection->backingType !== null ? gettype($value) : $enumReflection->getName();
        return sprintf("%sConstant [ public %s %s ] { %s }\n", \PHPStan\BetterReflection\Reflection\StringCast\ReflectionStringCastHelper::docCommentToString($enumCaseReflection, $indentDocComment), $type, $enumCaseReflection->name, $value);
    }
}
