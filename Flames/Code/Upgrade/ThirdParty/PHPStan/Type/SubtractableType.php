<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('subtractableType')]
interface SubtractableType extends \PHPStan\Type\Type
{
    public function getTypeWithoutSubtractedType(): \PHPStan\Type\Type;
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType): \PHPStan\Type\Type;
    public function getSubtractedType(): ?\PHPStan\Type\Type;
}
