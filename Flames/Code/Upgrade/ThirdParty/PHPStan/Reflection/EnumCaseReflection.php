<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('enumCaseReflection')]
final class EnumCaseReflection
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\Reflection\ClassReflection $declaringEnum;
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $reflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $backingValueType;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private bool $isDeprecated;
    private ?string $deprecatedDescription;
    /**
     * @param list<AttributeReflection> $attributes
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase $reflection
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\Reflection\ClassReflection $declaringEnum,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $reflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $backingValueType,
        array $attributes,
        DeprecationProvider $deprecationProvider
    )
    {
        $this->declaringEnum = $declaringEnum;
        $this->reflection = $reflection;
        $this->backingValueType = $backingValueType;
        $this->attributes = $attributes;
        $deprecation = $deprecationProvider->getEnumCaseDeprecation($reflection);
        if ($deprecation !== null) {
            $this->isDeprecated = \true;
            $this->deprecatedDescription = $deprecation->description;
        } elseif ($reflection->isDeprecated()) {
            $attributes = $this->reflection->getBetterReflection()->attributes;
            $this->isDeprecated = \true;
            $this->deprecatedDescription = DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
        } else {
            $this->isDeprecated = \false;
            $this->deprecatedDescription = null;
        }
    }
    public function getDeclaringEnum(): \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringEnum;
    }
    public function getName(): string
    {
        return $this->reflection->getName();
    }
    public function getEnumCaseObjectType(): EnumCaseObjectType
    {
        return new EnumCaseObjectType($this->declaringEnum->getName(), $this->reflection->getName());
    }
    public function getBackingValueType(): ?Type
    {
        return $this->backingValueType;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function getDeprecatedDescription(): ?string
    {
        return $this->deprecatedDescription;
    }
    /**
     * @return list<AttributeReflection>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
