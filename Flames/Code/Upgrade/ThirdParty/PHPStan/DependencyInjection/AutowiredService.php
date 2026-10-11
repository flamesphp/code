<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Registers a service in the DI container.
 *
 * Auto-adds service extension tags based on implemented interfaces.
 * Pass autoTag: false for services whose implemented interfaces
 * must not lead to the associated tags.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AutowiredService
{
    public ?string $name;
    public ?string $factory;
    /**
     * @var true|list<class-string>|class-string
     */
    public $as;
    public bool $autoTag;
    /**
     * @param true|list<class-string>|class-string $as
     */
    public function __construct(?string $name = null, ?string $factory = null, $as = \true, bool $autoTag = \true)
    {
        $this->name = $name;
        $this->factory = $factory;
        $this->as = $as;
        $this->autoTag = $autoTag;
    }
}
