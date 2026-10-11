<?php

declare (strict_types=1);
namespace PHPStan\Cache;

final class CacheItem
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $variableKey;
    /**
     * @var mixed
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $data;
    /**
     * @param mixed $data
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $variableKey,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $data
    )
    {
        $this->variableKey = $variableKey;
        $this->data = $data;
    }
    public function isVariableKeyValid(string $variableKey): bool
    {
        return $this->variableKey === $variableKey;
    }
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['variableKey'], $properties['data']);
    }
}
