<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Caching;

use Flames\Code\Upgrade\Caching\ValueObject\Storage\FileCacheStorage;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Filesystem\Filesystem;
final readonly class CacheFactory
{
    public function __construct(private Filesystem $fileSystem)
    {
    }
    /**
     * @api config factory
     */
    public function create(): \Flames\Code\Upgrade\Caching\Cache
    {
        $cacheDirectory = SimpleParameterProvider::provideStringParameter(Option::CACHE_DIR);
        // ensure cache directory exists
        if (!$this->fileSystem->exists($cacheDirectory)) {
            $this->fileSystem->mkdir($cacheDirectory);
        }
        $fileCacheStorage = new FileCacheStorage($cacheDirectory, $this->fileSystem);
        return new \Flames\Code\Upgrade\Caching\Cache($fileCacheStorage);
    }
}
