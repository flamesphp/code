<?php

declare (strict_types=1);
namespace PHPStan\Type;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use function preg_quote;
use function str_contains;
use function str_replace;
/**
 * The types of the class constants a late-resolved `static::FOO`, `static::FOO_*` or `T::*` stands
 * for, once the class is known - see ClassConstantAccessType.
 */
#[ReferencedByTurboExtension('classConstantPatternResolver')]
final class ClassConstantPatternResolver
{
    /**
     * The constants of the classes of $type whose names match $pattern, `*` matching anything.
     *
     * Which constants match a wildcard is known once the class is. It is not while $type is static
     * or a template type bound to a class that is not final, or an interface or an abstract class -
     * a subclass can declare more of them. Otherwise the class of $type is the one, as with a
     * single `static::FOO` resolved for the class a method is called on.
     *
     * With the native type of the parameter or of the return type, the result is decided together
     * with it, as TypehintHelper::decideType() does for other PHPDoc types.
     */
    public static function resolve(\PHPStan\Type\Type $type, string $pattern, ?\PHPStan\Type\Type $nativeType = null): \PHPStan\Type\Type
    {
        $result = self::resolveConstants($type, $pattern);
        if ($nativeType === null) {
            return $result;
        }
        return \PHPStan\Type\TypehintHelper::decideType($nativeType, $result);
    }
    private static function resolveConstants(\PHPStan\Type\Type $type, string $pattern): \PHPStan\Type\Type
    {
        $classReflections = $type->getObjectClassReflections();
        if ($classReflections === []) {
            return new \PHPStan\Type\ErrorType();
        }
        $isClassKnown = !$type instanceof \PHPStan\Type\StaticType && !\PHPStan\Type\TypeUtils::containsTemplateType($type);
        $constantTypes = [];
        foreach ($classReflections as $classReflection) {
            $classConstantTypes = str_contains($pattern, '*') ? self::resolveWildcard($classReflection, $pattern, $isClassKnown) : self::resolveConstant($classReflection, $pattern);
            if ($classConstantTypes instanceof \PHPStan\Type\MixedType) {
                return $classConstantTypes;
            }
            foreach ($classConstantTypes as $constantType) {
                $constantTypes[] = $constantType;
            }
        }
        if ($constantTypes === []) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeCombinator::union(...$constantTypes);
    }
    /**
     * @return list<Type>|MixedType
     */
    private static function resolveWildcard(ClassReflection $classReflection, string $pattern, bool $isClassKnown)
    {
        if (!$classReflection->isFinal() && (!$isClassKnown || $classReflection->isInterface() || $classReflection->isAbstract())) {
            return new \PHPStan\Type\MixedType();
        }
        // convert * into .*? and escape everything else so the constants can be matched against the pattern
        $regex = '{^' . str_replace('\*', '.*?', preg_quote($pattern)) . '$}D';
        $constantTypes = [];
        foreach ($classReflection->getNativeReflection()->getReflectionConstants() as $reflectionConstant) {
            $constantName = $reflectionConstant->betterClassConstantOrEnumCase->getName();
            if (Strings::match($constantName, $regex) === null) {
                continue;
            }
            if ($classReflection->isEnum() && $classReflection->hasEnumCase($constantName)) {
                $constantTypes[] = new EnumCaseObjectType($classReflection->getName(), $constantName);
                continue;
            }
            $constantTypes[] = $classReflection->getConstant($constantName)->getValueType();
        }
        return $constantTypes;
    }
    /**
     * A single constant is the one of the class it is looked up on, like `static::FOO` in a return
     * type is the value for the class the method is called on.
     *
     * @return list<Type>|MixedType
     */
    private static function resolveConstant(ClassReflection $classReflection, string $constantName)
    {
        if (!$classReflection->hasConstant($constantName)) {
            if (!$classReflection->isFinal()) {
                return new \PHPStan\Type\MixedType();
            }
            return [];
        }
        if ($classReflection->isEnum() && $classReflection->hasEnumCase($constantName)) {
            return [new EnumCaseObjectType($classReflection->getName(), $constantName)];
        }
        return [$classReflection->getConstant($constantName)->getValueType()];
    }
}
