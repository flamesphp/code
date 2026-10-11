<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter;

use LogicException;
use OutOfBoundsException;
use PhpParser\Node\Expr;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunctionAbstract as CoreReflectionFunctionAbstract;
use ReflectionParameter as CoreReflectionParameter;
use ReturnTypeWillChange;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use ValueError;
use function array_map;
use function count;
use function sprintf;
use function strtolower;
/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-immutable
 */
final class ReflectionParameter extends CoreReflectionParameter
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public BetterReflectionParameter $betterReflectionParameter;
    public function __construct(BetterReflectionParameter $betterReflectionParameter)
    {
        $this->betterReflectionParameter = $betterReflectionParameter;
        unset($this->name);
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return \PHPStan\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast::toString($this->betterReflectionParameter);
    }
    public function getName(): string
    {
        return $this->betterReflectionParameter->name;
    }
    public function getBetterReflection(): BetterReflectionParameter
    {
        return $this->betterReflectionParameter;
    }
    public function isPassedByReference(): bool
    {
        return $this->betterReflectionParameter->byRef;
    }
    public function canBePassedByValue(): bool
    {
        return !$this->betterReflectionParameter->isPassedByReference();
    }
    /**
     * @return ReflectionFunction|ReflectionMethod
     */
    public function getDeclaringFunction(): CoreReflectionFunctionAbstract
    {
        $function = $this->betterReflectionParameter->function;
        if ($function instanceof BetterReflectionMethod) {
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($function);
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction($function);
    }
    /** @return ReflectionClass|null */
    public function getDeclaringClass(): ?CoreReflectionClass
    {
        $declaringClass = $this->betterReflectionParameter->getDeclaringClass();
        if ($declaringClass === null) {
            return null;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($declaringClass);
    }
    /** @return ReflectionClass|null */
    public function getClass(): ?CoreReflectionClass
    {
        $type = $this->betterReflectionParameter->type;
        if ($type === null) {
            return null;
        }
        if ($type instanceof BetterReflectionIntersectionType) {
            return null;
        }
        if ($type instanceof BetterReflectionNamedType) {
            $classType = $type;
        } else {
            $unionTypes = $type->types;
            if (count($unionTypes) !== 2) {
                return null;
            }
            if (!$type->allowsNull()) {
                return null;
            }
            foreach ($unionTypes as $unionInnerType) {
                if (!$unionInnerType instanceof BetterReflectionNamedType) {
                    return null;
                }
                if ($unionInnerType->allowsNull()) {
                    continue;
                }
                $classType = $unionInnerType;
                break;
            }
        }
        try {
            /** @phpstan-ignore variable.undefined */
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($classType->getClass());
        } catch (LogicException $exception) {
            return null;
        }
    }
    public function isArray(): bool
    {
        return $this->isType($this->betterReflectionParameter->type, 'array');
    }
    public function isCallable(): bool
    {
        return $this->isType($this->betterReflectionParameter->type, 'callable');
    }
    /**
     * For isArray() and isCallable().
     * @param BetterReflectionNamedType|BetterReflectionUnionType|BetterReflectionIntersectionType|null $typeReflection
     */
    private function isType($typeReflection, string $type): bool
    {
        if ($typeReflection === null) {
            return \false;
        }
        if ($typeReflection instanceof BetterReflectionIntersectionType) {
            return \false;
        }
        $isOneOfAllowedTypes = static function (BetterReflectionType $namedType, string ...$types): bool {
            foreach ($types as $type) {
                if ($namedType instanceof BetterReflectionNamedType && strtolower($namedType->name) === $type) {
                    return \true;
                }
            }
            return \false;
        };
        if ($typeReflection instanceof BetterReflectionUnionType) {
            $unionTypes = $typeReflection->types;
            foreach ($unionTypes as $unionType) {
                if (!$isOneOfAllowedTypes($unionType, $type, 'null')) {
                    return \false;
                }
            }
            return \true;
        }
        return $isOneOfAllowedTypes($typeReflection, $type);
    }
    public function allowsNull(): bool
    {
        return $this->betterReflectionParameter->allowsNull();
    }
    public function getPosition(): int
    {
        return $this->betterReflectionParameter->parameterIndex;
    }
    public function isOptional(): bool
    {
        return $this->betterReflectionParameter->isOptional;
    }
    public function isVariadic(): bool
    {
        return $this->betterReflectionParameter->isVariadic;
    }
    public function isDefaultValueAvailable(): bool
    {
        return $this->betterReflectionParameter->default !== null;
    }
    #[ReturnTypeWillChange]
    public function getDefaultValue()
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }
    public function getDefaultValueExpression(): Expr
    {
        return $this->betterReflectionParameter->getDefaultValueExpression();
    }
    public function isDefaultValueConstant(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }
    public function getDefaultValueConstantName(): string
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }
    public function hasType(): bool
    {
        return $this->betterReflectionParameter->type !== null;
    }
    /**
     * @phpstan-ignore return.unusedType
     * @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
     */
    public function getType(): ?\ReflectionType
    {
        return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->betterReflectionParameter->type);
    }
    public function isPromoted(): bool
    {
        return $this->betterReflectionParameter->isPromoted;
    }
    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }
        if ($name !== null && $flags !== 0) {
            $attributes = \PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper::filterAttributesByInstance($this->betterReflectionParameter->getAttributes(), $name);
        } elseif ($name !== null) {
            $attributes = \PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper::filterAttributesByName($this->betterReflectionParameter->getAttributes(), $name);
        } else {
            $attributes = $this->betterReflectionParameter->attributes;
        }
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn(BetterReflectionAttribute $betterReflectionAttribute) => \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory::create($betterReflectionAttribute), $attributes);
    }
    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionParameter->name;
        }
        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
