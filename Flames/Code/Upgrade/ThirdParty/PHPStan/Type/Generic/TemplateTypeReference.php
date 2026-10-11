<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * A reference to a template type together with its variance at the point of usage.
 *
 * When a type contains template type parameters (e.g. `array<T>` or `Comparable<T>`),
 * this class pairs the TemplateType with its positional variance — whether T appears
 * in a covariant position (return type), contravariant position (parameter type),
 * invariant position, or bivariant position.
 *
 * Used by Type::getReferencedTemplateTypes() to report all template types within
 * a type along with their variance context. This information is used for:
 * - Template type inference (knowing the variance affects how types are inferred)
 * - Variance validation (checking that @template-covariant types only appear in
 *   covariant positions)
 */
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/TemplateTypeReference.cpp')]
final class TemplateTypeReference
{
    private \PHPStan\Type\Generic\TemplateType $type;
    private \PHPStan\Type\Generic\TemplateTypeVariance $positionVariance;
    public function __construct(\PHPStan\Type\Generic\TemplateType $type, \PHPStan\Type\Generic\TemplateTypeVariance $positionVariance)
    {
        $this->type = $type;
        $this->positionVariance = $positionVariance;
    }
    public function getType(): \PHPStan\Type\Generic\TemplateType
    {
        return $this->type;
    }
    public function getPositionVariance(): \PHPStan\Type\Generic\TemplateTypeVariance
    {
        return $this->positionVariance;
    }
}
