<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function count;
use function sprintf;
/** @api */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ValueOfType.cpp')]
final class ValueOfType implements \PHPStan\Type\CompoundType, \PHPStan\Type\LateResolvableType
{
    private \PHPStan\Type\Type $type;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    public function __construct(\PHPStan\Type\Type $type)
    {
        $this->type = $type;
    }
    public function getReferencedClasses(): array
    {
        return $this->type->getReferencedClasses();
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return $this->type->getReferencedTemplateTypes($positionVariance);
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $type instanceof self && $this->type->equals($type->type);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return sprintf('value-of<%s>', $this->type->describe($level));
    }
    public function isResolvable(): bool
    {
        return !\PHPStan\Type\TypeUtils::containsTemplateType($this->type);
    }
    protected function getResult(): \PHPStan\Type\Type
    {
        if ($this->type->isEnum()->yes()) {
            $enumCases = $this->type->getEnumCases();
            if ($enumCases === [] && $this->type instanceof TemplateType && (new \PHPStan\Type\ObjectType('BackedEnum'))->isSuperTypeOf($this->type->getBound())->yes()) {
                return new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
            }
            $valueTypes = [];
            foreach ($enumCases as $enumCase) {
                $valueType = $enumCase->getBackingValueType();
                if ($valueType === null) {
                    continue;
                }
                $valueTypes[] = $valueType;
            }
            if (count($valueTypes) === 0) {
                return new \PHPStan\Type\NeverType();
            }
            if (count($valueTypes) === 1) {
                return $valueTypes[0];
            }
            return new \PHPStan\Type\UnionType($valueTypes);
        }
        return $this->type->getIterableValueType();
    }
    /**
     * @param callable(Type): Type $cb
     */
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $type = $cb($this->type);
        if ($this->type === $type) {
            return $this;
        }
        return new self($type);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $type = $cb($this->type, $right->type);
        if ($this->type === $type) {
            return $this;
        }
        return new self($type);
    }
    public function toPhpDocNode(): TypeNode
    {
        return new GenericTypeNode(new IdentifierTypeNode('value-of'), [$this->type->toPhpDocNode()]);
    }
}
