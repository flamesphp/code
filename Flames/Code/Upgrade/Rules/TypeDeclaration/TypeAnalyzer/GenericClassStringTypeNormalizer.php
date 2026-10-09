<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer;

use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
final class GenericClassStringTypeNormalizer
{
    public function isAllGenericClassStringType(UnionType $unionType): bool
    {
        $found = array_all($unionType->getTypes(), fn($type) => $type instanceof GenericClassStringType);
        return $found;
    }
}
