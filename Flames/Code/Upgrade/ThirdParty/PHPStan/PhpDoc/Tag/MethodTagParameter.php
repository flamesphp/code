<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
/**
 * @api
 */
final class MethodTagParameter
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PassedByReference $passedByReference;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isOptional;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isVariadic;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $defaultValue;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PassedByReference $passedByReference,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isOptional,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isVariadic,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $defaultValue
    )
    {
        $this->type = $type;
        $this->passedByReference = $passedByReference;
        $this->isOptional = $isOptional;
        $this->isVariadic = $isVariadic;
        $this->defaultValue = $defaultValue;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }
    public function isOptional(): bool
    {
        return $this->isOptional;
    }
    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }
    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
}
