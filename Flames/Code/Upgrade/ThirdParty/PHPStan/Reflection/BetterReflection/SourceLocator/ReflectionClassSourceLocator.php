<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass;
final class ReflectionClassSourceLocator implements SourceLocator
{
    private Locator $astLocator;
    private ReflectionSourceStubber $reflectionSourceStubber;
    public function __construct(Locator $astLocator, ReflectionSourceStubber $reflectionSourceStubber)
    {
        $this->astLocator = $astLocator;
        $this->reflectionSourceStubber = $reflectionSourceStubber;
    }
    #[Override]
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        if (!$identifier->type->isClass()) {
            return null;
        }
        /** @var class-string $className */
        $className = $identifier->name;
        $stub = $this->reflectionSourceStubber->generateClassStub($className);
        if ($stub === null) {
            return null;
        }
        $reflection = new ReflectionClass($className);
        return $this->astLocator->findReflection($reflector, new LocatedSource($stub->stub, $reflection->getName(), null), new Identifier($reflection->getName(), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
    }
    #[Override]
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return [];
    }
}
