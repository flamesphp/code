<?php

declare (strict_types=1);
namespace PHPStan\Cache;

final class MemoryCacheStorage implements \PHPStan\Cache\CacheStorage
{
    /** @var array<string, CacheItem> */
    private array $storage = [];
    /**
     * @return mixed|null
     */
    public function load(string $key, string $variableKey)
    {
        if (!isset($this->storage[$key])) {
            return null;
        }
        $item = $this->storage[$key];
        if (!($item->variableKey === $variableKey)) {
            return null;
        }
        return $item->data;
    }
    /**
     * @param mixed $data
     */
    public function save(string $key, string $variableKey, $data): void
    {
        $this->storage[$key] = new \PHPStan\Cache\CacheItem($variableKey, $data);
    }
}
