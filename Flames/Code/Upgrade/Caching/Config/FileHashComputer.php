<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Caching\Config;

use Flames\Code\Upgrade\Application\VersionResolver;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\FileSystem\FilePathHelper;
/**
 * Inspired by https://github.com/symplify/easy-coding-standard/blob/e598ab54686e416788f28fcfe007fd08e0f371d9/packages/changed-files-detector/src/FileHashComputer.php
 */
final readonly class FileHashComputer
{
    public function __construct(private FilePathHelper $filePathHelper)
    {
    }
    public function compute(string $filePath): string
    {
        $this->ensureIsPhp($filePath);
        $parametersHash = SimpleParameterProvider::hashForCacheInvalidation();
        // relative config path, so the hash (and the whole cache) is not tied to one directory
        $relativeFilePath = $this->filePathHelper->relativePath($this->filePathHelper->resolveRealPath($filePath));
        return sha1($relativeFilePath . $parametersHash . VersionResolver::PACKAGE_VERSION);
    }
    private function ensureIsPhp(string $filePath): void
    {
        $fileExtension = pathinfo($filePath, \PATHINFO_EXTENSION);
        if ($fileExtension === 'php') {
            return;
        }
        throw new ShouldNotHappenException(sprintf(
            // getRealPath() cannot be used, as it breaks in phar
            'Provide only PHP file, ready for Dependency Injection. "%s" given',
            $filePath
        ));
    }
}
