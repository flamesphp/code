<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[AutowiredService(\null, \null, \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider::class)]
final class LazyTypeNodeResolverExtensionRegistryProvider implements \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider
{
    private Container $container;
    private ?\PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry $registry = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getRegistry(): \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
    {
        return $this->registry ??= new \PHPStan\PhpDoc\TypeNodeResolverExtensionAwareRegistry($this->container->getByType(\PHPStan\PhpDoc\TypeNodeResolver::class), $this->container->getExtensionsCollection(\PHPStan\PhpDoc\TypeNodeResolverExtension::class)->getAll());
    }
}
