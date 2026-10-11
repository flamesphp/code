<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\SubstractableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
/** @api */
#[\PHPStan\Type\InstanceofDeprecated('Type::isObject()')]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ObjectWithoutClassType.cpp')]
class ObjectWithoutClassType implements \PHPStan\Type\SubtractableType
{
    use ObjectTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGeneralizableTypeTrait;
    use SubstractableTypeTrait;
    private ?\PHPStan\Type\Type $subtractedType;
    /** @api */
    public function __construct(?\PHPStan\Type\Type $subtractedType = null)
    {
        if ($subtractedType instanceof \PHPStan\Type\NeverType) {
            $subtractedType = null;
        }
        $this->subtractedType = $subtractedType;
    }
    public function getReferencedClasses(): array
    {
        return [];
    }
    public function getObjectClassNames(): array
    {
        return [];
    }
    public function getObjectClassReflections(): array
    {
        return [];
    }
    public function getClassStringType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ClassStringType();
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return \PHPStan\Type\AcceptsResult::createFromBoolean($type instanceof self || $type instanceof \PHPStan\Type\ObjectShapeType || $type->getObjectClassNames() !== []);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        if ($type instanceof self) {
            if ($this->subtractedType === null) {
                return \PHPStan\Type\IsSuperTypeOfResult::createYes();
            }
            if ($type->subtractedType !== null) {
                $isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
                if ($isSuperType->yes()) {
                    return $isSuperType;
                }
            }
            return \PHPStan\Type\IsSuperTypeOfResult::createMaybe();
        }
        if ($type instanceof \PHPStan\Type\ObjectShapeType) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type->getObjectClassNames() === []) {
            return \PHPStan\Type\IsSuperTypeOfResult::createNo();
        }
        if ($this->subtractedType === null) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        return $this->subtractedType->isSuperTypeOf($type)->negate();
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        if (!$type instanceof self) {
            return \false;
        }
        if ($this->subtractedType === null) {
            if ($type->subtractedType === null) {
                return \true;
            }
            return \false;
        }
        if ($type->subtractedType === null) {
            return \false;
        }
        return $this->subtractedType->equals($type->subtractedType);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return $level->handle(static fn(): string => 'object', static fn(): string => 'object', fn(): string => 'object' . $this->describeSubtractedType($this->subtractedType, $level));
    }
    public function getEnumCases(): array
    {
        return [];
    }
    public function getEnumCaseObject(): ?EnumCaseObjectType
    {
        return null;
    }
    public function subtract(\PHPStan\Type\Type $type): \PHPStan\Type\Type
    {
        if ($type instanceof self) {
            return new \PHPStan\Type\NeverType();
        }
        if ($this->subtractedType !== null) {
            $type = \PHPStan\Type\TypeCombinator::union($this->subtractedType, $type);
        }
        return new self($type);
    }
    public function getTypeWithoutSubtractedType(): \PHPStan\Type\Type
    {
        return new self();
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType): \PHPStan\Type\Type
    {
        return new self($subtractedType);
    }
    public function getSubtractedType(): ?\PHPStan\Type\Type
    {
        return $this->subtractedType;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;
        if ($subtractedType !== $this->subtractedType) {
            return new self($subtractedType);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if ($this->subtractedType === null) {
            return $this;
        }
        return new self();
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove): ?\PHPStan\Type\Type
    {
        // object is the top of the object hierarchy, so removal is exactly the
        // subtraction - also when an earlier subtraction already lowered
        // isSuperTypeOf() from yes to maybe, which used to remove nothing at all
        if ($this->isSuperTypeOf($typeToRemove)->no()) {
            return null;
        }
        return $this->subtract($typeToRemove);
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        if (!$exponent instanceof \PHPStan\Type\NeverType && !$this->isSuperTypeOf($exponent)->no()) {
            return \PHPStan\Type\TypeCombinator::union($this, $exponent);
        }
        return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('object');
    }
    public function hasTemplateOrLateResolvableType(): bool
    {
        return \false;
    }
}
