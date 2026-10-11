<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
#[AutowiredService(\null, \null, \PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider::class)]
final class LazyReflectionProviderProvider implements \PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider
{
    private Container $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getReflectionProvider(): ReflectionProvider
    {
        return $this->container->getByType(ReflectionProvider::class);
    }
}
