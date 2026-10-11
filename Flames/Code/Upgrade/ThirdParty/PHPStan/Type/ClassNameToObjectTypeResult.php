<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Result of projecting a "class-name-or-object" `Type` to its corresponding
 * `ObjectType` for an `instanceof` / `is_a` check.
 *
 * `$uncertainty` is `true` when the projection lost information that prevents
 * a definite yes/no decision later — e.g. a runtime class string was kept
 * symbolically instead of being resolved to a concrete object type. Composite
 * types OR-fold the flag across their members.
 */
#[ReferencedByTurboExtension('classNameToObjectTypeResult')]
final class ClassNameToObjectTypeResult
{
    /**
     * @readonly
     */
    public \PHPStan\Type\Type $type;
    /**
     * @readonly
     */
    public bool $uncertainty;
    public function __construct(\PHPStan\Type\Type $type, bool $uncertainty)
    {
        $this->type = $type;
        $this->uncertainty = $uncertainty;
    }
}
