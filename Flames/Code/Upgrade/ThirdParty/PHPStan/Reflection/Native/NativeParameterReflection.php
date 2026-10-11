<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/NativeParameterReflection.cpp')]
final class NativeParameterReflection implements ParameterReflection
{
    private string $name;
    private bool $optional;
    private Type $type;
    private PassedByReference $passedByReference;
    private bool $variadic;
    private ?Type $defaultValue;
    public function __construct(string $name, bool $optional, Type $type, PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue)
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->type = $type;
        $this->passedByReference = $passedByReference;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
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
    /** Used when merging signatures where only some of them declare this parameter. */
    public function toOptional(): self
    {
        if ($this->optional) {
            return $this;
        }
        return new self($this->name, \true, $this->type, $this->passedByReference, $this->variadic, $this->defaultValue);
    }
    public function union(self $other): self
    {
        return new self($this->name, $this->optional && $other->optional, TypeCombinator::union($this->type, $other->type), $this->passedByReference->combine($other->passedByReference), $this->variadic && $other->variadic, $this->optional && $other->optional ? $this->defaultValue : null);
    }
}
