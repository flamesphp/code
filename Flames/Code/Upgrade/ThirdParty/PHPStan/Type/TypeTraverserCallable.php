<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('typeTraverserCallable')]
interface TypeTraverserCallable
{
    /**
     * @param callable(Type): Type $traverse
     */
    public function traverse(\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type;
}
