<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Removing\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final class ArgumentRemover
{
    /**
     * @param mixed $value
     */
    public function __construct(private readonly string $class, private readonly string $method, private readonly int $position, /**
     * @readonly
     */
    private $value)
    {
        RectorAssert::className($this->class);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->class);
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getPosition(): int
    {
        return $this->position;
    }
    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
