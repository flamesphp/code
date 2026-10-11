<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('reflectionProviderStaticAccessor')]
final class ReflectionProviderStaticAccessor
{
    private static ?\PHPStan\Reflection\ReflectionProvider $instance = null;
    private function __construct()
    {
    }
    public static function registerInstance(\PHPStan\Reflection\ReflectionProvider $reflectionProvider): void
    {
        self::$instance = $reflectionProvider;
    }
    public static function getInstance(): \PHPStan\Reflection\ReflectionProvider
    {
        if (self::$instance === null) {
            throw new \PHPStan\Reflection\MissingStaticAccessorInstanceException();
        }
        return self::$instance;
    }
}
