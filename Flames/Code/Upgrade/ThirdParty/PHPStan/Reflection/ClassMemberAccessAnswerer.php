<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

/**
 * Answers questions about visibility and access rights for class members
 * (properties, methods, constants) from the current analysis context.
 *
 * This interface is the Scope's role as an access control checker. It is
 * passed as a parameter to Type methods like getMethod(), getProperty(),
 * getConstant(), etc., so the type system can enforce visibility rules
 * (public/protected/private) based on where the access occurs.
 *
 * The primary implementation is MutatingScope. A secondary implementation,
 * OutOfClassScope, is used when accessing members from outside any class.
 *
 * @api
 * @api-do-not-implement
 */
interface ClassMemberAccessAnswerer
{
    /**
     * @phpstan-assert-if-true !null $this->getClassReflection()
     */
    public function isInClass(): bool;
    public function getClassReflection(): ?\PHPStan\Reflection\ClassReflection;
    /**
     * @deprecated Use canReadProperty() or canWriteProperty()
     */
    public function canAccessProperty(\PHPStan\Reflection\PropertyReflection $propertyReflection): bool;
    public function canReadProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool;
    public function canWriteProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection): bool;
    public function canCallMethod(\PHPStan\Reflection\MethodReflection $methodReflection): bool;
    public function canAccessConstant(\PHPStan\Reflection\ClassConstantReflection $constantReflection): bool;
}
