<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeStrategy;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
/**
 * A reference to the template-type subject of a ConditionalType inside one of its
 * branches, narrowed by what the branch knows: `subject & target` where the condition
 * holds, `subject ~ target` where it does not.
 *
 * The narrowing is computed from the template type right away, and this type behaves
 * exactly like that narrowed template type as long as the subject stays unresolved.
 * The narrowing is recomputed from whatever the subject resolves to: it is
 * `(stdClass|false) ~ false` that gives stdClass for the else branch of
 * `(T is false ? never : T)` - resolving `T~false` on its own only hands back the type
 * T resolved to - and `S ~ U` with a template type as the target cannot be narrowed
 * at all before both resolve. The resolvers of template types therefore traverse into
 * it instead of substituting it.
 */
#[ReferencedByTurboExtension('narrowedSubjectType')]
final class NarrowedSubjectType implements TemplateType, \PHPStan\Type\LateResolvableType
{
    private TemplateType $subject;
    private \PHPStan\Type\Type $target;
    private bool $conditionHolds;
    private TemplateType $narrowed;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    private function __construct(TemplateType $subject, \PHPStan\Type\Type $target, bool $conditionHolds, TemplateType $narrowed)
    {
        $this->subject = $subject;
        $this->target = $target;
        $this->conditionHolds = $conditionHolds;
        $this->narrowed = $narrowed;
    }
    public static function create(TemplateType $subject, \PHPStan\Type\Type $target, bool $conditionHolds): \PHPStan\Type\Type
    {
        $narrowed = self::narrow($subject, $target, $conditionHolds);
        if (!$narrowed instanceof TemplateType || $subject->isArgument()) {
            // nothing to recompute once the template type is gone from the narrowing,
            // and seen from inside its own function it never resolves
            return $narrowed;
        }
        return new self($subject, $target, $conditionHolds, $narrowed);
    }
    /**
     * Replaces the references to $subject in a branch of a conditional type on it with the
     * subject as the branch knows it.
     */
    public static function narrowReferences(\PHPStan\Type\Type $branch, TemplateType $subject, \PHPStan\Type\Type $target, bool $conditionHolds): \PHPStan\Type\Type
    {
        $narrowedSubject = self::create($subject, $target, $conditionHolds);
        return \PHPStan\Type\TypeTraverser::map($branch, static fn(\PHPStan\Type\Type $type, callable $traverse): \PHPStan\Type\Type => $subject->equals($type) ? $narrowedSubject : $traverse($type));
    }
    private static function narrow(\PHPStan\Type\Type $subject, \PHPStan\Type\Type $target, bool $conditionHolds): \PHPStan\Type\Type
    {
        if ($conditionHolds) {
            return \PHPStan\Type\TypeCombinator::intersect($subject, $target);
        }
        return \PHPStan\Type\TypeCombinator::remove($subject, $target);
    }
    public function getName(): string
    {
        return $this->narrowed->getName();
    }
    public function getScope(): TemplateTypeScope
    {
        return $this->narrowed->getScope();
    }
    public function getBound(): \PHPStan\Type\Type
    {
        return $this->narrowed->getBound();
    }
    public function getDefault(): ?\PHPStan\Type\Type
    {
        return $this->narrowed->getDefault();
    }
    public function toArgument(): TemplateType
    {
        return $this->narrowed->toArgument();
    }
    public function isArgument(): bool
    {
        return $this->narrowed->isArgument();
    }
    public function isValidVariance(\PHPStan\Type\Type $a, \PHPStan\Type\Type $b, bool $strict = \false): \PHPStan\Type\IsSuperTypeOfResult
    {
        return $this->narrowed->isValidVariance($a, $b, $strict);
    }
    public function getVariance(): TemplateTypeVariance
    {
        return $this->narrowed->getVariance();
    }
    public function getStrategy(): TemplateTypeStrategy
    {
        return $this->narrowed->getStrategy();
    }
    public function getReferencedClasses(): array
    {
        return $this->narrowed->getReferencedClasses();
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return $this->narrowed->getReferencedTemplateTypes($positionVariance);
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $this->narrowed->equals($type instanceof self ? $type->narrowed : $type);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return $this->narrowed->describe($level);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        return $this->narrowed->isSuperTypeOf($type);
    }
    public function isResolvable(): bool
    {
        return \false;
    }
    protected function getResult(): \PHPStan\Type\Type
    {
        return $this->narrowed;
    }
    /**
     * @param callable(Type): Type $cb
     */
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        return $this->rebuild($cb($this->subject), $cb($this->target), $cb);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        return $this->rebuild($cb($this->subject, $right->subject), $cb($this->target, $right->target), static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $cb($type, $right->narrowed));
    }
    /**
     * @param callable(Type): Type $cb
     */
    private function rebuild(\PHPStan\Type\Type $subject, \PHPStan\Type\Type $target, callable $cb): \PHPStan\Type\Type
    {
        if (!$subject instanceof TemplateType || $subject->getName() !== $this->subject->getName() || !$subject->getScope()->equals($this->subject->getScope())) {
            // the subject resolved to another type: narrow that one instead
            return self::narrow($subject, $target, $this->conditionHolds);
        }
        // still the same template type, at most narrowed by an enclosing conditional
        // type or turned into an argument: the narrowing stays as computed
        $narrowed = $cb($this->narrowed);
        if (!$narrowed instanceof TemplateType || $subject->isArgument()) {
            return $narrowed;
        }
        if ($subject === $this->subject && $target === $this->target && $narrowed === $this->narrowed) {
            return $this;
        }
        return new self($subject, $target, $this->conditionHolds, $narrowed);
    }
    public function toPhpDocNode(): TypeNode
    {
        return $this->narrowed->toPhpDocNode();
    }
}
