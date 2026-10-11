<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class StringToClassConstant
{
    public function __construct(private string $string, private string $class, private string $constant)
    {
        RectorAssert::className($this->class);
    }
    public function getString(): string
    {
        return $this->string;
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getConstant(): string
    {
        return $this->constant;
    }
}
