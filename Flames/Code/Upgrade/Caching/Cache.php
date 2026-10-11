<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Caching;

use Flames\Code\Upgrade\Caching\Contract\ValueObject\Storage\CacheStorageInterface;
use Flames\Code\Upgrade\Caching\Enum\CacheKey;
final readonly class Cache
{
    public function __construct(private CacheStorageInterface $cacheStorage)
    {
    }
    /**
     * @param CacheKey::* $variableKey
     * @return mixed|null
     */
    public function load(string $key, string $variableKey)
    {
        return $this->cacheStorage->load($key, $variableKey);
    }
    /**
     * @param CacheKey::* $variableKey
     * @param mixed $data
     */
    public function save(string $key, string $variableKey, $data): void
    {
        $this->cacheStorage->save($key, $variableKey, $data);
    }
    public function clear(): void
    {
        $this->cacheStorage->clear();
    }
    public function clean(string $cacheKey): void
    {
        $this->cacheStorage->clean($cacheKey);
    }
}
