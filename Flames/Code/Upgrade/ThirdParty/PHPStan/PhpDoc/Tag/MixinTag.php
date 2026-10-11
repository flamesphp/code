<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class MixinTag
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
}
