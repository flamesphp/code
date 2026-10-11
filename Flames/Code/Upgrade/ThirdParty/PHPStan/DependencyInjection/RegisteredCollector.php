<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Registers a collector in the DI container on the set rule level.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RegisteredCollector
{
    public int $level;
    public function __construct(int $level)
    {
        $this->level = $level;
    }
}
