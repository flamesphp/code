<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class StaticCallToNew
{
    public function __construct(private string $class, private string $method)
    {
        RectorAssert::className($this->class);
        RectorAssert::methodName($this->method);
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getMethod(): string
    {
        return $this->method;
    }
}
