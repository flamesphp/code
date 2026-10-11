<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use function sprintf;
/** @internal */
final class ReflectionPropertyStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionProperty $propertyReflection, bool $indentDocComment = \true): string
    {
        $stateModifier = '';
        if (!(bool) ($propertyReflection->modifiers & \ReflectionProperty::IS_STATIC)) {
            $stateModifier = $propertyReflection->declaredAtCompileTime ? ' <default>' : ' <dynamic>';
        }
        $type = $propertyReflection->type;
        return sprintf('%sProperty [%s %s%s%s%s $%s ]', \PHPStan\BetterReflection\Reflection\StringCast\ReflectionStringCastHelper::docCommentToString($propertyReflection, $indentDocComment), $stateModifier, self::visibilityToString($propertyReflection), (bool) ($propertyReflection->modifiers & \ReflectionProperty::IS_STATIC) ? ' static' : '', $propertyReflection->isReadOnly() ? ' readonly' : '', $type !== null ? sprintf(' %s', \PHPStan\BetterReflection\Reflection\StringCast\ReflectionTypeStringCast::toString($type)) : '', $propertyReflection->name);
    }
    /** @psalm-pure */
    private static function visibilityToString(ReflectionProperty $propertyReflection): string
    {
        if ((bool) ($propertyReflection->modifiers & \ReflectionProperty::IS_PROTECTED)) {
            return 'protected';
        }
        if ((bool) ($propertyReflection->modifiers & \ReflectionProperty::IS_PRIVATE)) {
            return 'private';
        }
        return 'public';
    }
}
