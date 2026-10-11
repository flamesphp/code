<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[AutowiredService(\null, \null, \PHPStan\Type\TypeAliasResolverProvider::class)]
final class LazyTypeAliasResolverProvider implements \PHPStan\Type\TypeAliasResolverProvider
{
    private Container $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getTypeAliasResolver(): \PHPStan\Type\TypeAliasResolver
    {
        return $this->container->getByType(\PHPStan\Type\TypeAliasResolver::class);
    }
}
