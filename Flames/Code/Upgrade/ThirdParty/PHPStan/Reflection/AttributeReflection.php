<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Type;
/**
 * Reflection for a PHP attribute (PHP 8.0+).
 *
 * Represents a single attribute applied to a class, method, property, function,
 * parameter, or constant. Provides the attribute's class name and the types of
 * its constructor arguments.
 *
 * Returned by the getAttributes() method on ExtendedMethodReflection,
 * ExtendedPropertyReflection, FunctionReflection, ClassConstantReflection, etc.
 *
 * @api
 */
final class AttributeReflection
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $name;
    /**
     * @var array<string, Type>
     */
    private array $argumentTypes;
    /**
     * @param array<string, Type> $argumentTypes Argument types keyed by parameter name
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $name,
        array $argumentTypes
    )
    {
        $this->name = $name;
        $this->argumentTypes = $argumentTypes;
    }
    public function getName(): string
    {
        return $this->name;
    }
    /** @return array<string, Type> */
    public function getArgumentTypes(): array
    {
        return $this->argumentTypes;
    }
}
