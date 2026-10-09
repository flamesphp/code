<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Removing\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class RemoveFuncCallArg
{
    public function __construct(private string $function, private int $argumentPosition)
    {
        RectorAssert::functionName($this->function);
    }
    public function getFunction(): string
    {
        return $this->function;
    }
    public function getArgumentPosition(): int
    {
        return $this->argumentPosition;
    }
}
