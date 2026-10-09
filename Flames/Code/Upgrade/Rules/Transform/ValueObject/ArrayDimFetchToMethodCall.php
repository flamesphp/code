<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PHPStan\Type\ObjectType;
final readonly class ArrayDimFetchToMethodCall
{
    public function __construct(private ObjectType $objectType, private string $method, private ?string $setMethod = null, private ?string $existsMethod = null, private ?string $unsetMethod = null)
    {
    }
    public function getObjectType(): ObjectType
    {
        return $this->objectType;
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getSetMethod(): ?string
    {
        return $this->setMethod;
    }
    public function getExistsMethod(): ?string
    {
        return $this->existsMethod;
    }
    public function getUnsetMethod(): ?string
    {
        return $this->unsetMethod;
    }
}
