<?php

declare (strict_types=1);
namespace PHPStan\Type\Regex;

final class RegexNonCapturingGroup
{
    /**
     * @readonly
     */
    private ?\PHPStan\Type\Regex\RegexAlternation $alternation;
    /**
     * @readonly
     */
    private bool $inOptionalQuantification;
    /**
     * @readonly
     * @var \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $parent;
    /**
     * @readonly
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $resetGroupCounter;
    /**
     * @param \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null $parent
     */
    public function __construct(
        ?\PHPStan\Type\Regex\RegexAlternation $alternation,
        bool $inOptionalQuantification,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $parent,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $resetGroupCounter
    )
    {
        $this->alternation = $alternation;
        $this->inOptionalQuantification = $inOptionalQuantification;
        $this->parent = $parent;
        $this->resetGroupCounter = $resetGroupCounter;
    }
    /** @phpstan-assert-if-true !null $this->getAlternationId() */
    private function inAlternation(): bool
    {
        return $this->alternation !== null;
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
        return $this->alternation !== null || $this->inOptionalQuantification || $this->parent !== null && $this->parent->isOptional();
    }
    public function inOptionalQuantification(): bool
    {
        return $this->inOptionalQuantification;
    }
    public function isTopLevel(): bool
    {
        return $this->parent === null || $this->parent instanceof \PHPStan\Type\Regex\RegexNonCapturingGroup && $this->parent->isTopLevel();
    }
    /**
     * @return \PHPStan\Type\Regex\RegexCapturingGroup|\PHPStan\Type\Regex\RegexNonCapturingGroup|null
     */
    public function getParent()
    {
        return $this->parent;
    }
    public function resetsGroupCounter(): bool
    {
        return $this->resetGroupCounter;
    }
}
