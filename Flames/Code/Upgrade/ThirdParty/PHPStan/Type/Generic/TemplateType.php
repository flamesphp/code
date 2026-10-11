<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\CompoundType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
/**
 * @api
 * @api-do-not-implement
 */
#[ReferencedByTurboExtension('templateType')]
interface TemplateType extends CompoundType
{
    /** @return non-empty-string */
    public function getName(): string;
    public function getScope(): \PHPStan\Type\Generic\TemplateTypeScope;
    public function getBound(): Type;
    public function getDefault(): ?Type;
    public function toArgument(): \PHPStan\Type\Generic\TemplateType;
    public function isArgument(): bool;
    public function isValidVariance(Type $a, Type $b, bool $strict = \false): IsSuperTypeOfResult;
    public function getVariance(): \PHPStan\Type\Generic\TemplateTypeVariance;
    public function getStrategy(): \PHPStan\Type\Generic\TemplateTypeStrategy;
}
