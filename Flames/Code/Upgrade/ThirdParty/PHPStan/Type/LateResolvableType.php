<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
/** @api */
#[ReferencedByTurboExtension('lateResolvableType')]
interface LateResolvableType
{
    public function resolve(): \PHPStan\Type\Type;
    public function isResolvable(): bool;
}
