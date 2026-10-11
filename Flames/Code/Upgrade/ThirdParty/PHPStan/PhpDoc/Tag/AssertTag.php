<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/** @api */
#[ReferencedByTurboExtension('assertTag')]
final class AssertTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    /**
     * @var self::NULL|self::IF_TRUE|self::IF_FALSE
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $if;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\PhpDoc\Tag\AssertTagParameter $parameter;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $negated;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $equality;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isExplicit;
    public const NULL = '';
    public const IF_TRUE = 'true';
    public const IF_FALSE = 'false';
    private ?Type $originalType = null;
    /**
     * @param self::NULL|self::IF_TRUE|self::IF_FALSE $if
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $if,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\PhpDoc\Tag\AssertTagParameter $parameter,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $negated,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $equality,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isExplicit
    )
    {
        $this->if = $if;
        $this->type = $type;
        $this->parameter = $parameter;
        $this->negated = $negated;
        $this->equality = $equality;
        $this->isExplicit = $isExplicit;
    }
    /**
     * @return self::NULL|self::IF_TRUE|self::IF_FALSE
     */
    public function getIf(): string
    {
        return $this->if;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function getOriginalType(): Type
    {
        return $this->originalType ??= $this->type;
    }
    public function getParameter(): \PHPStan\PhpDoc\Tag\AssertTagParameter
    {
        return $this->parameter;
    }
    public function isNegated(): bool
    {
        return $this->negated;
    }
    public function isEquality(): bool
    {
        return $this->equality;
    }
    /**
     * @return static
     */
    public function withType(Type $type): \PHPStan\PhpDoc\Tag\TypedTag
    {
        $tag = new self($this->if, $type, $this->parameter, $this->negated, $this->equality, $this->isExplicit);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
    public function withParameter(\PHPStan\PhpDoc\Tag\AssertTagParameter $parameter): self
    {
        $tag = new self($this->if, $this->type, $parameter, $this->negated, $this->equality, $this->isExplicit);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
    public function negate(): self
    {
        if ($this->equality) {
            throw new ShouldNotHappenException();
        }
        $tag = new self($this->if, $this->type, $this->parameter, !$this->negated, $this->equality, $this->isExplicit);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
    public function isExplicit(): bool
    {
        return $this->isExplicit;
    }
    public function toImplicit(): self
    {
        return new self($this->if, $this->type, $this->parameter, $this->negated, $this->equality, \false);
    }
}
