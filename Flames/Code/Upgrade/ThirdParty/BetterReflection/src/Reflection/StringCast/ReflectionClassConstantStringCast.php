<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use function gettype;
use function is_array;
use function sprintf;
/** @internal */
final class ReflectionClassConstantStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionClassConstant $constantReflection, bool $indentDocComment = \true): string
    {
        /** @psalm-var scalar|array<scalar> $value */
        $value = $constantReflection->getValue();
        return sprintf("%sConstant [ %s%s %s %s ] { %s }\n", \PHPStan\BetterReflection\Reflection\StringCast\ReflectionStringCastHelper::docCommentToString($constantReflection, $indentDocComment), $constantReflection->isFinal() ? 'final ' : '', self::visibilityToString($constantReflection), gettype($value), $constantReflection->name, is_array($value) ? 'Array' : (string) $value);
    }
    /** @psalm-pure */
    private static function visibilityToString(ReflectionClassConstant $constantReflection): string
    {
        if ((bool) ($constantReflection->modifiers & \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant::IS_PROTECTED_COMPATIBILITY)) {
            return 'protected';
        }
        if ($constantReflection->modifiers === \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant::IS_PRIVATE_COMPATIBILITY) {
            return 'private';
        }
        return 'public';
    }
}
