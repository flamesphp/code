<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
#[AutowiredService]
final class OptimizedDirectorySourceLocatorRepository
{
    private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory $factory;
    /** @var array<string, OptimizedDirectorySourceLocator> */
    private array $locators = [];
    public function __construct(\PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory $factory)
    {
        $this->factory = $factory;
    }
    /**
     * With a batch, a locator not created yet is added to it, and cannot be used before the batch
     * is scanned.
     */
    public function getOrCreate(string $directory, ?\PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorBatch $batch = null): \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocator
    {
        if (array_key_exists($directory, $this->locators)) {
            return $this->locators[$directory];
        }
        $this->locators[$directory] = $batch !== null ? $batch->createByDirectory($directory) : $this->factory->createByDirectory($directory);
        return $this->locators[$directory];
    }
}
