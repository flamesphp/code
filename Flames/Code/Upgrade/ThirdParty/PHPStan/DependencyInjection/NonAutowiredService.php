<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Registers a non-autowired named service in the DI container.
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NonAutowiredService
{
    public string $name;
    public ?string $factory;
    public function __construct(string $name, ?string $factory = null)
    {
        $this->name = $name;
        $this->factory = $factory;
    }
}
