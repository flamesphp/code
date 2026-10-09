<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Visibility\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class ChangeMethodVisibility
{
    public function __construct(private string $class, private string $method, private int $visibility)
    {
        RectorAssert::className($this->class);
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getVisibility(): int
    {
        return $this->visibility;
    }
}
