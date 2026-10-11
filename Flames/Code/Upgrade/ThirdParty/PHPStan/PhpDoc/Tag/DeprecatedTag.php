<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

/**
 * @api
 */
final class DeprecatedTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $message;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $message
    )
    {
        $this->message = $message;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
