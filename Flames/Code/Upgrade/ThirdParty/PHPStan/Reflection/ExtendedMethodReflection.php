<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * Extended method reflection with additional metadata beyond MethodReflection.
 *
 * This interface exists to allow PHPStan to add new method query methods in minor
 * versions without breaking existing MethodsClassReflectionExtension implementations.
 * Extension developers should implement MethodReflection, not this interface — PHPStan
 * wraps MethodReflection implementations to provide ExtendedMethodReflection.
 *
 * Provides access to:
 * - Extended parameter signatures (ExtendedParametersAcceptor with PHPDoc/native types)
 * - Named argument variants (different signatures when using named arguments)
 * - Type assertions (@phpstan-assert annotations)
 * - Self-out types (@phpstan-self-out for fluent interfaces)
 * - Purity information (@phpstan-pure/@phpstan-impure)
 * - PHP attributes (including #[\NoDiscard])
 * - Resolved PHPDoc block
 *
 * This is the return type of Type::getMethod() and Scope::getMethodReflection().
 *
 * @api
 * @api-do-not-implement
 */
#[ReferencedByTurboExtension('extendedMethodReflection')]
interface ExtendedMethodReflection extends \PHPStan\Reflection\MethodReflection
{
    /** @return list<ExtendedParametersAcceptor> */
    public function getVariants(): array;
    /** @internal */
    public function getOnlyVariant(): \PHPStan\Reflection\ExtendedParametersAcceptor;
    /**
     * Returns alternative signatures used when the method is called with named arguments.
     * Returns null if the named argument variants are the same as regular variants.
     *
     * @return list<ExtendedParametersAcceptor>|null
     */
    public function getNamedArgumentsVariants(): ?array;
    public function acceptsNamedArguments(): TrinaryLogic;
    public function getAsserts(): \PHPStan\Reflection\Assertions;
    /**
     * Used for fluent interfaces where calling a method changes the generic
     * type parameters of $this (e.g. a builder pattern).
     */
    public function getSelfOutType(): ?Type;
    public function returnsByReference(): TrinaryLogic;
    public function isFinalByKeyword(): TrinaryLogic;
    /**
     * @return \PHPStan\TrinaryLogic|bool
     */
    public function isAbstract();
    /**
     * @return \PHPStan\TrinaryLogic|bool
     */
    public function isBuiltin();
    /**
     * In most cases hasSideEffects() is more practical as it also accounts
     * for void return type (methods returning void are always impure).
     */
    public function isPure(): TrinaryLogic;
    /**
     * @return array<string, TrinaryLogic>
     */
    public function getPureUnlessCallableIsImpureParameters(): array;
    /**
     * @return array<string, TrinaryLogic>
     */
    public function getPureUnlessParameterPassedParameters(): array;
    /** @return list<AttributeReflection> */
    public function getAttributes(): array;
    /**
     * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
     * PHPStan reports this during analysis regardless of PHP version.
     */
    public function mustUseReturnValue(): TrinaryLogic;
    public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock;
}
