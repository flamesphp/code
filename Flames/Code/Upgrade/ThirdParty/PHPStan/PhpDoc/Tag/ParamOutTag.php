<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class ParamOutTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type
    )
    {
        $this->type = $type;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function withType(Type $type): self
    {
        return new self($type);
    }
}
