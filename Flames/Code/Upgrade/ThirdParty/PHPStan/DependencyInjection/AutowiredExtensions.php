<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Wires a constructor parameter of type ExtensionsCollection to the collection
 * of all registered extensions implementing the given extension interface.
 *
 * The interface must be marked with the #[ExtensionInterface] attribute.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredExtensionsExtension.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class AutowiredExtensions
{
    /**
     * @var class-string
     */
    public string $of;
    /**
     * @param class-string $of
     */
    public function __construct(string $of)
    {
        $this->of = $of;
    }
}
