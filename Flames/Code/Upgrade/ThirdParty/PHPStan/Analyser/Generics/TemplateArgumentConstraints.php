<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Generics;

use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\Type;
use function array_pop;
use function spl_object_id;
/**
 * Persistent collection of inference facts. Branches share their prefix; appending
 * and joining take constant time. Only the resolver flattens the collection.
 * No fact retains a scope, expression result, or mutable collection context.
 */
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/TemplateArgumentConstraints.cpp')]
final class TemplateArgumentConstraints
{
    /**
     * @readonly
     */
    private ?self $left;
    /**
     * @readonly
     */
    private ?self $right;
    /**
     * @readonly
     * @var array{UnresolvedTemplateArgumentType, Type|null, TemplateTypeVariance|null, bool}|null
     */
    private ?array $fact;
    /**
     * @readonly
     * @var self|null
     */
    private ?self $until;
    /**
     * @param array{UnresolvedTemplateArgumentType, Type|null, TemplateTypeVariance|null, bool}|null $fact
     * @param self|null $until where the walk of $right stops - see withRecordedFacts()
     */
    private function __construct(?self $left = null, ?self $right = null, ?array $fact = null, ?self $until = null)
    {
        $this->left = $left;
        $this->right = $right;
        $this->fact = $fact;
        $this->until = $until;
    }
    public static function createEmpty(): self
    {
        return new self();
    }
    public function isEmpty(): bool
    {
        return $this->left === null && $this->right === null && $this->fact === null;
    }
    public function merge(self $other): self
    {
        if ($this === $other || $other->isEmpty()) {
            return $this;
        }
        if ($this->isEmpty()) {
            return $other;
        }
        return new self($this, $other);
    }
    /**
     * Joins the facts $exit holds beyond $entry: what a recorded walk added
     * between two scopes whose constraints were $entry and $exit - carried
     * over to another walk that replays that stretch instead of walking it.
     */
    public function withRecordedFacts(?self $entry, self $exit): self
    {
        if ($entry === null) {
            return $this->merge($exit);
        }
        if ($exit === $entry || $exit->isEmpty()) {
            return $this;
        }
        return new self($this, $exit, \null, $entry);
    }
    public function withSite(UnresolvedTemplateArgumentType $marker): self
    {
        if ($marker->getSite()->getAttribute(\PHPStan\Analyser\Generics\TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE) === \true) {
            return $this;
        }
        return new self($this, \null, [$marker, null, null, \false]);
    }
    public function withSend(UnresolvedTemplateArgumentType $marker, Type $type, TemplateTypeVariance $variance): self
    {
        return new self($this, \null, [$marker, $type, $variance, \false]);
    }
    public function withLowerBound(UnresolvedTemplateArgumentType $marker, Type $type): self
    {
        return new self($this, \null, [$marker, $type, null, \false]);
    }
    public function withUnconstrainingSend(UnresolvedTemplateArgumentType $marker): self
    {
        return new self($this, \null, [$marker, null, null, \true]);
    }
    /** @return iterable<array{UnresolvedTemplateArgumentType, Type|null, TemplateTypeVariance|null, bool}> */
    public function getFacts(): iterable
    {
        $stack = [[$this, \false, null]];
        $visited = [];
        while ($stack !== []) {
            [$current, $expanded, $until] = array_pop($stack);
            if ($expanded) {
                if ($current->fact !== null) {
                    yield $current->fact;
                }
                continue;
            }
            if ($current === $until) {
                continue;
            }
            $id = spl_object_id($current);
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = \true;
            $stack[] = [$current, \true, null];
            if ($current->right !== null) {
                $stack[] = [$current->right, \false, $current->until ?? $until];
            }
            if ($current->left === null) {
                continue;
            }
            $stack[] = [$current->left, \false, $until];
        }
    }
}
