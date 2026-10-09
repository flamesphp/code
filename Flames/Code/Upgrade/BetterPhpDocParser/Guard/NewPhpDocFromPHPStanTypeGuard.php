<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Guard;

use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
final class NewPhpDocFromPHPStanTypeGuard
{
    public function isLegal(Type $type): bool
    {
        if ($type instanceof UnionType) {
            return $this->isLegalUnionType($type);
        }
        return \true;
    }
    private function isLegalUnionType(UnionType $type): bool
    {
        $found = array_all($type->getTypes(), fn($unionType) => !$unionType instanceof MixedType);
        return $found;
    }
}
