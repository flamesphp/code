<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Closure;
use function sprintf;
/**
 * Directory locators created together and filled in by one scan once they are all known: a file
 * reachable from two of them is looked at once, and the cost of the scan is paid once instead of
 * per directory. The locators cannot be asked anything until scan() has run.
 *
 * Created by OptimizedDirectorySourceLocatorFactory::createBatch().
 */
final class OptimizedDirectorySourceLocatorBatch
{
    /**
     * @var Closure(string): string[]
     */
    private Closure $findFiles;
    /**
     * @var Closure(): OptimizedDirectorySourceLocator
     */
    private Closure $createLocator;
    /**
     * @var Closure(list<array{non-empty-string, string[], OptimizedDirectorySourceLocator}>): void
     */
    private Closure $scan;
    /** @var list<array{non-empty-string, string[], OptimizedDirectorySourceLocator}> */
    private array $requests = [];
    /**
     * @param Closure(string): string[] $findFiles
     * @param Closure(): OptimizedDirectorySourceLocator $createLocator
     * @param Closure(list<array{non-empty-string, string[], OptimizedDirectorySourceLocator}>): void $scan
     */
    public function __construct(Closure $findFiles, Closure $createLocator, Closure $scan)
    {
        $this->findFiles = $findFiles;
        $this->createLocator = $createLocator;
        $this->scan = $scan;
    }
    public function createByDirectory(string $directory): \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocator
    {
        return $this->add(sprintf('odsl-%s', $directory), ($this->findFiles)($directory));
    }
    /**
     * @param string[] $files
     * @param non-empty-string&literal-string $uniqueCacheIdentifier
     */
    public function createByFiles(array $files, string $uniqueCacheIdentifier): \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocator
    {
        return $this->add($uniqueCacheIdentifier, $files);
    }
    /**
     * Fills in every locator created since the last scan.
     */
    public function scan(): void
    {
        $requests = $this->requests;
        $this->requests = [];
        if ($requests === []) {
            return;
        }
        ($this->scan)($requests);
    }
    /**
     * @param non-empty-string $cacheKey
     * @param string[] $files
     */
    private function add(string $cacheKey, array $files): \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocator
    {
        $locator = ($this->createLocator)();
        $this->requests[] = [$cacheKey, $files, $locator];
        return $locator;
    }
}
