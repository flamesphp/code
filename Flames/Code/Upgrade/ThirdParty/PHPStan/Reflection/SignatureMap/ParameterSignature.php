<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
final class ParameterSignature
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $optional;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $nativeType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PassedByReference $passedByReference;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $variadic;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $defaultValue;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $outType;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $optional,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $nativeType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PassedByReference $passedByReference,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $variadic,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $defaultValue,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $outType
    )
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->type = $type;
        $this->nativeType = $nativeType;
        $this->passedByReference = $passedByReference;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
        $this->outType = $outType;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function isOptional(): bool
    {
        return $this->optional;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function getNativeType(): Type
    {
        return $this->nativeType;
    }
    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }
    public function isVariadic(): bool
    {
        return $this->variadic;
    }
    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
    public function getOutType(): ?Type
    {
        return $this->outType;
    }
}
