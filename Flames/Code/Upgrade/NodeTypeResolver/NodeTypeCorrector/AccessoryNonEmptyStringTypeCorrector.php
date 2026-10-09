<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeCorrector;

use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
final class AccessoryNonEmptyStringTypeCorrector
{
    public function correct(Type $mainType): Type
    {
        if (!$mainType instanceof IntersectionType) {
            return $mainType;
        }
        if (!$mainType->isNonEmptyString()->yes()) {
            return $mainType;
        }
        return new StringType();
    }
}
