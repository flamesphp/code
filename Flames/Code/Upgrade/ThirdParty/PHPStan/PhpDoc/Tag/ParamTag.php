<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class ParamTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isVariadic;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isVariadic
    )
    {
        $this->type = $type;
        $this->isVariadic = $isVariadic;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }
    public function withType(Type $type): self
    {
        return new self($type, $this->isVariadic);
    }
}
