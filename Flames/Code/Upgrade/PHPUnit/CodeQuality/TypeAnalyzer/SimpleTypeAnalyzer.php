<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\TypeAnalyzer;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
final class SimpleTypeAnalyzer
{
    public static function isNullableType(Type $type): bool
    {
        if (!$type instanceof UnionType) {
            return \false;
        }
        if (!TypeCombinator::containsNull($type)) {
            return \false;
        }
        return count($type->getTypes()) === 2;
    }
}
