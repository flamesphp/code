<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/StringNeverAcceptingObjectWithToStringType.cpp')]
class StringNeverAcceptingObjectWithToStringType extends \PHPStan\Type\StringType
{
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        $result = parent::isSuperTypeOf($type);
        if (!$type instanceof self) {
            $result = $result->and(\PHPStan\Type\IsSuperTypeOfResult::createMaybe());
        }
        return $result;
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        return parent::accepts($type, \true);
    }
}
