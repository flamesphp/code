<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @api
 * @api-do-not-implement
 */
#[ReferencedByTurboExtension('extendedParameterReflection')]
interface ExtendedParameterReflection extends \PHPStan\Reflection\ParameterReflection
{
    public function getPhpDocType(): Type;
    public function hasNativeType(): bool;
    public function getNativeType(): Type;
    public function getOutType(): ?Type;
    public function isImmediatelyInvokedCallable(): TrinaryLogic;
    public function getClosureThisType(): ?Type;
    /**
     * @return list<AttributeReflection>
     */
    public function getAttributes(): array;
    public function getAllowedConstants(): ?\PHPStan\Reflection\ParameterAllowedConstants;
    /**
     * @param list<ConstantReflection> $constants Global and/or class constant reflections
     */
    public function checkAllowedConstants(array $constants): \PHPStan\Reflection\AllowedConstantsResult;
    public function isPureUnlessCallableIsImpureParameter(): TrinaryLogic;
    public function isPureUnlessParameterPassedParameter(): TrinaryLogic;
}
