<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;
/** @api */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ConditionalTypeForParameter.cpp')]
final class ConditionalTypeForParameter implements \PHPStan\Type\CompoundType, \PHPStan\Type\LateResolvableType
{
    private string $parameterName;
    private \PHPStan\Type\Type $target;
    private \PHPStan\Type\Type $if;
    private \PHPStan\Type\Type $else;
    private bool $negated;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    private ?TemplateType $parameterTemplateType = null;
    private ?\PHPStan\Type\Type $normalizedIf = null;
    private ?\PHPStan\Type\Type $normalizedElse = null;
    public function __construct(string $parameterName, \PHPStan\Type\Type $target, \PHPStan\Type\Type $if, \PHPStan\Type\Type $else, bool $negated)
    {
        $this->parameterName = $parameterName;
        $this->target = $target;
        $this->if = $if;
        $this->else = $else;
        $this->negated = $negated;
    }
    public function getParameterName(): string
    {
        return $this->parameterName;
    }
    public function getTarget(): \PHPStan\Type\Type
    {
        return $this->target;
    }
    public function getIf(): \PHPStan\Type\Type
    {
        return $this->if;
    }
    public function getElse(): \PHPStan\Type\Type
    {
        return $this->else;
    }
    public function isNegated(): bool
    {
        return $this->negated;
    }
    public function changeParameterName(string $parameterName): self
    {
        $type = new self($parameterName, $this->target, $this->if, $this->else, $this->negated);
        $type->parameterTemplateType = $this->parameterTemplateType;
        return $type;
    }
    /**
     * Narrows the references to the template type the parameter is declared with along with
     * the parameter: `@param T $param` makes `($param is X ? A : B)` narrow T to `T & X` in A
     * and to `T ~ X` in B, the way `(T is X ? A : B)` does (see NarrowedSubjectType). Only
     * sound when nothing but the parameter binds T.
     */
    public function narrowTemplateType(TemplateType $templateType): self
    {
        $type = new self($this->parameterName, $this->target, $this->if, $this->else, $this->negated);
        $type->parameterTemplateType = $templateType;
        return $type;
    }
    /**
     * Replaces every ConditionalTypeForParameter inside $type with the ConditionalType on the
     * subject its parameter resolves to. $getSubjectType is called with the parameter name
     * including the leading `$`; returning null leaves that conditional unresolved.
     *
     * Shared by everything that resolves a declared conditional type against concrete
     * subjects: ResolvedFunctionVariant (`@return`, `@param`, `@param-out`,
     * `@param-closure-this`), TypeSpecifier (`@phpstan-assert`) and ConditionalTypeResolver
     * (`@throws`, `@phpstan-self-out`).
     *
     * @param callable(string): ?Type $getSubjectType
     */
    public static function resolveInType(\PHPStan\Type\Type $type, callable $getSubjectType): \PHPStan\Type\Type
    {
        if (!$type->hasTemplateOrLateResolvableType()) {
            return $type;
        }
        return \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use ($getSubjectType): \PHPStan\Type\Type {
            if ($type instanceof self) {
                $subjectType = $getSubjectType($type->getParameterName());
                if ($subjectType !== null) {
                    // Resolve the branches first, then convert — avoids infinite loop when
                    // the subject contains a ConditionalTypeForParameter with a colliding parameter name.
                    return $type->resolveWithSubject($subjectType, $getSubjectType);
                }
            }
            return $traverse($type);
        });
    }
    /**
     * A condition on the same parameter nested in a branch sees the subject as the branch
     * knows it: `subject & target` where this condition holds, `subject ~ target` where it
     * does not. `($key is Model ? A : ($key is Arrayable ? B : C))` asks whether the
     * non-Model part of the argument is Arrayable.
     *
     * @param callable(string): ?Type $getSubjectType
     */
    private function resolveWithSubject(\PHPStan\Type\Type $subject, callable $getSubjectType): \PHPStan\Type\Type
    {
        $target = self::resolveInType($this->target, $getSubjectType);
        $getNarrowedSubjectType = fn(bool $conditionHolds): callable => fn(string $parameterName): ?\PHPStan\Type\Type => $parameterName === $this->parameterName ? $conditionHolds ? \PHPStan\Type\TypeCombinator::intersect($subject, $target) : \PHPStan\Type\TypeCombinator::remove($subject, $target) : $getSubjectType($parameterName);
        return new \PHPStan\Type\ConditionalType($subject, $target, self::resolveInType($this->getNormalizedIf(), $getNarrowedSubjectType(!$this->negated)), self::resolveInType($this->getNormalizedElse(), $getNarrowedSubjectType($this->negated)), $this->negated);
    }
    public function toConditional(\PHPStan\Type\Type $subject): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ConditionalType($subject, $this->target, $this->getNormalizedIf(), $this->getNormalizedElse(), $this->negated);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->if->isSuperTypeOf($type->if)->and($this->else->isSuperTypeOf($type->else));
        }
        return $this->isSuperTypeOfDefault($type);
    }
    public function getReferencedClasses(): array
    {
        return array_merge($this->target->getReferencedClasses(), $this->if->getReferencedClasses(), $this->else->getReferencedClasses());
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return array_merge($this->target->getReferencedTemplateTypes($positionVariance), $this->if->getReferencedTemplateTypes($positionVariance), $this->else->getReferencedTemplateTypes($positionVariance));
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $type instanceof self && $this->parameterName === $type->parameterName && $this->target->equals($type->target) && $this->if->equals($type->if) && $this->else->equals($type->else);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return sprintf('(%s %s %s ? %s : %s)', $this->parameterName, $this->negated ? 'is not' : 'is', $this->target->describe($level), $this->if->describe($level), $this->else->describe($level));
    }
    public function isResolvable(): bool
    {
        return \false;
    }
    protected function getResult(): \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union($this->getNormalizedIf(), $this->getNormalizedElse());
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $target = $cb($this->target);
        $if = $cb($this->getNormalizedIf());
        $else = $cb($this->getNormalizedElse());
        if ($this->target === $target && $this->getNormalizedIf() === $if && $this->getNormalizedElse() === $else) {
            return $this;
        }
        return new self($this->parameterName, $target, $if, $else, $this->negated);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $target = $cb($this->target, $right->target);
        $if = $cb($this->getNormalizedIf(), $right->getNormalizedIf());
        $else = $cb($this->getNormalizedElse(), $right->getNormalizedElse());
        if ($this->target === $target && $this->getNormalizedIf() === $if && $this->getNormalizedElse() === $else) {
            return $this;
        }
        return new self($this->parameterName, $target, $if, $else, $this->negated);
    }
    private function getNormalizedIf(): \PHPStan\Type\Type
    {
        return $this->normalizedIf ??= $this->parameterTemplateType === null ? $this->if : \PHPStan\Type\NarrowedSubjectType::narrowReferences($this->if, $this->parameterTemplateType, $this->target, !$this->negated);
    }
    private function getNormalizedElse(): \PHPStan\Type\Type
    {
        return $this->normalizedElse ??= $this->parameterTemplateType === null ? $this->else : \PHPStan\Type\NarrowedSubjectType::narrowReferences($this->else, $this->parameterTemplateType, $this->target, $this->negated);
    }
    public function toPhpDocNode(): TypeNode
    {
        return new ConditionalTypeForParameterNode($this->parameterName, $this->target->toPhpDocNode(), $this->if->toPhpDocNode(), $this->else->toPhpDocNode(), $this->negated);
    }
}
