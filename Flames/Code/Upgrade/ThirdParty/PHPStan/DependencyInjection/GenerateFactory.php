<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Generates concrete factory implementation based on the passed interface name.
 *
 * This looks at and compares constructor of the class with the attribute
 * against the "create" method of the passed interface.
 *
 * It subtracts "create" parameters from the constructor parameters.
 * And the rest is autowired from the dependency injection container.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class GenerateFactory
{
    /**
     * @var class-string
     */
    public string $interface;
    /**
     * @var class-string
     */
    public ?string $resultType;
    /**
     * @param class-string $interface
     * @param class-string $resultType
     */
    public function __construct(string $interface, ?string $resultType = null)
    {
        $this->interface = $interface;
        $this->resultType = $resultType;
    }
}
