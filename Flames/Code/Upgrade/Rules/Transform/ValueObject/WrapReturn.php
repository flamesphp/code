<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class WrapReturn
{
    public function __construct(private string $type, private string $method, private bool $isArrayWrap)
    {
        RectorAssert::className($this->type);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->type);
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function isArrayWrap(): bool
    {
        return $this->isArrayWrap;
    }
}
