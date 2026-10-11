<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class StaticCallToFuncCall
{
    public function __construct(private string $class, private string $method, private string $function)
    {
        RectorAssert::className($this->class);
        RectorAssert::methodName($this->method);
        RectorAssert::functionName($this->function);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->class);
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getFunction(): string
    {
        return $this->function;
    }
}
