<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass as CoreReflectionClass;
use function class_exists;
use function interface_exists;
use function trait_exists;
final class RewriteClassAliasSourceLocator implements SourceLocator
{
    private SourceLocator $originalSourceLocator;
    public function __construct(SourceLocator $originalSourceLocator)
    {
        $this->originalSourceLocator = $originalSourceLocator;
    }
    #[Override]
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        if (!$identifier->type->isClass()) {
            return $this->originalSourceLocator->locateIdentifier($reflector, $identifier);
        }
        if (class_exists($identifier->name, \false) || interface_exists($identifier->name, \false) || trait_exists($identifier->name, \false)) {
            $classReflection = new CoreReflectionClass($identifier->name);
            return $this->originalSourceLocator->locateIdentifier($reflector, new Identifier($classReflection->getName(), $identifier->type));
        }
        return $this->originalSourceLocator->locateIdentifier($reflector, $identifier);
    }
    #[Override]
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->originalSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
