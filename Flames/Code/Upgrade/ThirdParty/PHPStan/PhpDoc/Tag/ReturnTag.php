<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class ReturnTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isExplicit;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isExplicit
    )
    {
        $this->type = $type;
        $this->isExplicit = $isExplicit;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function isExplicit(): bool
    {
        return $this->isExplicit;
    }
    public function withType(Type $type): self
    {
        return new self($type, $this->isExplicit);
    }
    public function toImplicit(): self
    {
        return new self($this->type, \false);
    }
}
