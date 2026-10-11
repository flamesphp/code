<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass;
use function class_exists;
final class SkipClassAliasSourceLocator implements SourceLocator
{
    private SourceLocator $sourceLocator;
    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }
    #[Override]
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        if ($identifier->type->isClass()) {
            $className = $identifier->name;
            if (!class_exists($className, \false)) {
                return $this->sourceLocator->locateIdentifier($reflector, $identifier);
            }
            $reflection = new ReflectionClass($className);
            if ($reflection->getName() === 'ReturnTypeWillChange') {
                return $this->sourceLocator->locateIdentifier($reflector, $identifier);
            }
            if ($reflection->getFileName() === \false) {
                return $this->sourceLocator->locateIdentifier($reflector, $identifier);
            }
            return null;
        }
        return $this->sourceLocator->locateIdentifier($reflector, $identifier);
    }
    #[Override]
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
