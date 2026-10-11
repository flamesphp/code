<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Constant;

use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
final class RuntimeConstantReflection implements ConstantReflection
{
    private string $name;
    private Type $valueType;
    private ?string $fileName;
    private TrinaryLogic $isDeprecated;
    private ?string $deprecatedDescription;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private bool $internal;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(string $name, Type $valueType, ?string $fileName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription, array $attributes, bool $internal)
    {
        $this->name = $name;
        $this->valueType = $valueType;
        $this->fileName = $fileName;
        $this->isDeprecated = $isDeprecated;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->attributes = $attributes;
        $this->internal = $internal;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function describe(): string
    {
        return $this->name;
    }
    public function isBuiltin(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->internal);
    }
    public function getValueType(): Type
    {
        return $this->valueType;
    }
    public function getFileName(): ?string
    {
        return $this->fileName;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return $this->isDeprecated;
    }
    public function getDeprecatedDescription(): ?string
    {
        return $this->deprecatedDescription;
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
