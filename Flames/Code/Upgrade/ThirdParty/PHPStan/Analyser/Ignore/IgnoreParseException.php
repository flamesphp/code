<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use Exception;
final class IgnoreParseException extends Exception
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $phpDocLine;
    public function __construct(
        string $message,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $phpDocLine
    )
    {
        $this->phpDocLine = $phpDocLine;
        parent::__construct($message);
    }
    public function getPhpDocLine(): int
    {
        return $this->phpDocLine;
    }
}
