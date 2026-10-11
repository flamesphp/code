<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Analyser\ResultCache\ClassResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\DirectoryResultCacheValueExtension;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
#[AutowiredService(\null, \null, \PHPStan\Analyser\DeclarationDependencyTracker::class)]
final class ValueDependencyDeclarationDependencyTracker implements \PHPStan\Analyser\DeclarationDependencyTracker
{
    private \PHPStan\Analyser\ValueDependencyCollector $valueDependencyCollector;
    public function __construct(\PHPStan\Analyser\ValueDependencyCollector $valueDependencyCollector)
    {
        $this->valueDependencyCollector = $valueDependencyCollector;
    }
    public function trackValueDependency(ClassReflection $classReflection, string $extensionClass, string $key): void
    {
        $this->valueDependencyCollector->recordForClass($classReflection->getName(), $extensionClass, $key);
    }
    public function trackFileDependency(ClassReflection $classReflection, string $file): void
    {
        $this->valueDependencyCollector->recordForClass($classReflection->getName(), FileResultCacheValueExtension::class, $this->valueDependencyCollector->fileHelper->normalizePath($file));
    }
    public function trackDirectoryDependency(ClassReflection $classReflection, string $directory, string $pattern = '*'): void
    {
        $this->valueDependencyCollector->recordForClass($classReflection->getName(), DirectoryResultCacheValueExtension::class, \PHPStan\Analyser\ResultCache\DirectoryResultCacheValueExtension::createKey(\rtrim($this->valueDependencyCollector->fileHelper->normalizePath($directory), '/\\'), $pattern));
    }
    public function trackClassDependency(ClassReflection $classReflection, string $className): void
    {
        $this->valueDependencyCollector->recordForClass($classReflection->getName(), ClassResultCacheValueExtension::class, ClassResultCacheValueExtension::createKey($className));
    }
}
