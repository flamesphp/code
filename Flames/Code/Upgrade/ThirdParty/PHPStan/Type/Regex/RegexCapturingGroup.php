<?php

declare (strict_types=1);
namespace PHPStan\Type\Regex;

use PHPStan\Type\Type;
final class RegexCapturingGroup
{
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $id;
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $name;
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?\PHPStan\Type\Regex\RegexAlternation $alternation;
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $inOptionalQuantification;
    /**
     * @readonly
     * @var \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $parent;
    /**
     * @readonly
     */
    private Type $type;
    /**
     * @readonly
     */
    private bool $forceNonOptional;
    /**
     * @readonly
     */
    private ?Type $forceType;
    /**
     * @readonly
     */
    private bool $forceAlternationBranchMatched;
    /**
     * @param \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null $parent
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $id,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?\PHPStan\Type\Regex\RegexAlternation $alternation,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $inOptionalQuantification,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $parent,
        Type $type,
        bool $forceNonOptional = \false,
        ?Type $forceType = null,
        bool $forceAlternationBranchMatched = \false
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->alternation = $alternation;
        $this->inOptionalQuantification = $inOptionalQuantification;
        $this->parent = $parent;
        $this->type = $type;
        $this->forceNonOptional = $forceNonOptional;
        $this->forceType = $forceType;
        $this->forceAlternationBranchMatched = $forceAlternationBranchMatched;
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function forceNonOptional(): self
    {
        return new self($this->id, $this->name, $this->alternation, $this->inOptionalQuantification, $this->parent, $this->type, \true, $this->forceType, $this->forceAlternationBranchMatched);
    }
    /**
     * Unlike forceNonOptional(), the group stays optional when it is optional
     * within its alternation branch, e.g. because of a `?` quantifier.
     */
    public function forceAlternationBranchMatched(): self
    {
        return new self($this->id, $this->name, $this->alternation, $this->inOptionalQuantification, $this->parent, $this->type, $this->forceNonOptional, $this->forceType, \true);
    }
    public function forceType(Type $type): self
    {
        return new self($this->id, $this->name, $this->alternation, $this->inOptionalQuantification, $this->parent, $type, $this->forceNonOptional, $this->forceType, $this->forceAlternationBranchMatched);
    }
    /**
     * @param \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup $parent
     */
    public function withParent($parent): self
    {
        return new self($this->id, $this->name, $this->alternation, $this->inOptionalQuantification, $parent, $this->type, $this->forceNonOptional, $this->forceType, $this->forceAlternationBranchMatched);
    }
    public function resetsGroupCounter(): bool
    {
        return $this->parent instanceof \PHPStan\Type\Regex\RegexNonCapturingGroup && $this->parent->resetGroupCounter;
    }
    /**
     * @phpstan-assert-if-true !null $this->getAlternationId()
     * @phpstan-assert-if-true !null $this->getAlternation()
     */
    public function inAlternation(): bool
    {
        return $this->alternation !== null;
    }
    public function getAlternation(): ?\PHPStan\Type\Regex\RegexAlternation
    {
        return $this->alternation;
    }
    public function getAlternationId(): ?int
    {
        if ($this->alternation === null) {
            return null;
        }
        return $this->alternation->alternationId;
    }
    public function isOptional(): bool
    {
        if ($this->forceNonOptional) {
            return \false;
        }
        if ($this->forceAlternationBranchMatched) {
            return $this->isOptionalWithinAlternationBranch();
        }
        return $this->alternation !== null || $this->inOptionalQuantification || $this->parent !== null && $this->parent->isOptional();
    }
    private function isOptionalWithinAlternationBranch(): bool
    {
        if ($this->inOptionalQuantification) {
            return \true;
        }
        $parent = $this->parent;
        while ($parent !== null && $parent->getAlternationId() === $this->getAlternationId()) {
            if ($parent->inOptionalQuantification()) {
                return \true;
            }
            $parent = $parent->getParent();
        }
        return \false;
    }
    public function inOptionalQuantification(): bool
    {
        return $this->inOptionalQuantification;
    }
    public function inOptionalAlternation(): bool
    {
        if (!($this->alternation !== null)) {
            return \false;
        }
        $parent = $this->parent;
        while ($parent !== null && $parent->getAlternationId() === $this->getAlternationId()) {
            if (!$parent instanceof \PHPStan\Type\Regex\RegexNonCapturingGroup) {
                return \false;
            }
            $parent = $parent->parent;
        }
        return $parent !== null && $parent->isOptional();
    }
    public function isTopLevel(): bool
    {
        return $this->parent === null || $this->parent instanceof \PHPStan\Type\Regex\RegexNonCapturingGroup && $this->parent->isTopLevel();
    }
    /** @phpstan-assert-if-true !null $this->getName() */
    public function isNamed(): bool
    {
        return $this->name !== null;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function getType(): Type
    {
        if ($this->forceType !== null) {
            return $this->forceType;
        }
        return $this->type;
    }
    /**
     * @return \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null
     */
    public function getParent()
    {
        return $this->parent;
    }
}
