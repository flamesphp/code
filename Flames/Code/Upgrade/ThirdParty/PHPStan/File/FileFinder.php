<?php

declare (strict_types=1);
namespace PHPStan\File;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function file_exists;
use function is_file;
use function sort;
final class FileFinder
{
    private \PHPStan\File\FileExcluder $fileExcluder;
    private \PHPStan\File\FileHelper $fileHelper;
    /**
     * @var string[]
     */
    private array $fileExtensions;
    private \PHPStan\File\DirectoryWalker $directoryWalker;
    /**
     * @param string[] $fileExtensions
     */
    public function __construct(\PHPStan\File\FileExcluder $fileExcluder, \PHPStan\File\FileHelper $fileHelper, array $fileExtensions, \PHPStan\File\DirectoryWalker $directoryWalker)
    {
        $this->fileExcluder = $fileExcluder;
        $this->fileHelper = $fileHelper;
        $this->fileExtensions = $fileExtensions;
        $this->directoryWalker = $directoryWalker;
    }
    /**
     * @param string[] $paths
     */
    public function findFiles(array $paths): \PHPStan\File\FileFinderResult
    {
        return $this->doFindFiles($paths, \false);
    }
    /**
     * Like findFiles(), but the directory walk is shared with the other FileFinder - see
     * DirectoryWalker. Only for the callers that run once per analysis, before it starts.
     *
     * @param string[] $paths
     */
    public function findFilesCached(array $paths): \PHPStan\File\FileFinderResult
    {
        return $this->doFindFiles($paths, \true);
    }
    /**
     * @param string[] $paths
     */
    private function doFindFiles(array $paths, bool $cached): \PHPStan\File\FileFinderResult
    {
        $onlyFiles = \true;
        $files = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (!file_exists($path)) {
                throw new \PHPStan\File\PathNotFoundException($path);
            } else {
                $walkedFiles = $cached ? $this->directoryWalker->walkCached($path, $this->fileExtensions) : $this->directoryWalker->walk($path, $this->fileExtensions);
                foreach ($walkedFiles as $walkedFile) {
                    $files[] = $walkedFile;
                    $onlyFiles = \false;
                }
            }
        }
        $files = array_filter($files, fn(string $file): bool => !$this->fileExcluder->isExcludedFromAnalysing($file));
        sort($files);
        return new \PHPStan\File\FileFinderResult(array_values(array_unique(array_map(fn(string $file): string => $this->fileHelper->normalizePath($file), $files))), $onlyFiles);
    }
}
