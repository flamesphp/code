<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_values;
/**
 * Registered in the DI container by AutowiredExtensionsExtension,
 * once for each interface marked with #[ExtensionInterface].
 *
 * @template T of object
 * @implements ExtensionsCollection<T>
 */
#[ReferencedByTurboExtension('lazyExtensionsCollection')]
final class LazyExtensionsCollection implements \PHPStan\DependencyInjection\ExtensionsCollection
{
    private \PHPStan\DependencyInjection\Container $container;
    private string $tagName;
    /** @var list<T>|null */
    private ?array $extensions = null;
    public function __construct(\PHPStan\DependencyInjection\Container $container, string $tagName)
    {
        $this->container = $container;
        $this->tagName = $tagName;
    }
    public function getAll(): array
    {
        return $this->extensions ??= array_values($this->container->getServicesByTag($this->tagName));
    }
}
