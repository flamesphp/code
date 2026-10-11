<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
#[AutowiredService('reflectionProviderFactory')]
final class ReflectionProviderFactory
{
    private ReflectionProvider $staticReflectionProvider;
    public function __construct(
        #[AutowiredParameter('@betterReflectionProvider')]
        ReflectionProvider $staticReflectionProvider
    )
    {
        $this->staticReflectionProvider = $staticReflectionProvider;
    }
    public function create(): ReflectionProvider
    {
        return new \PHPStan\Reflection\ReflectionProvider\MemoizingReflectionProvider($this->staticReflectionProvider);
    }
}
