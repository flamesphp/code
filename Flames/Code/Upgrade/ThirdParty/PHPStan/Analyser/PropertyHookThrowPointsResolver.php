<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/PropertyHookThrowPointsResolver.cpp')]
final class PropertyHookThrowPointsResolver
{
    private bool $implicitThrows;
    public function __construct(
        #[AutowiredParameter('%exceptions.implicitThrows%')]
        bool $implicitThrows
    )
    {
        $this->implicitThrows = $implicitThrows;
    }
    /**
     * @param 'get'|'set' $hookName
     * @return InternalThrowPoint[]
     */
    public function getThrowPointsFromPropertyHook(\PHPStan\Analyser\MutatingScope $scope, PropertyFetch $propertyFetch, PhpPropertyReflection $propertyReflection, string $hookName): array
    {
        $scopeFunction = $scope->getFunction();
        if ($scopeFunction instanceof PhpMethodFromParserNodeReflection && $scopeFunction->hookForProperty !== null && $propertyFetch->var instanceof Variable && $propertyFetch->var->name === 'this' && $propertyFetch->name instanceof Identifier && $propertyFetch->name->name === $scopeFunction->hookForProperty) {
            return [];
        }
        $declaringClass = $propertyReflection->getDeclaringClass();
        if (!$propertyReflection->hasHook($hookName)) {
            if ($propertyReflection->isPrivate() || $propertyReflection->isFinal()->yes() || $declaringClass->isFinal()) {
                return [];
            }
            if ($this->implicitThrows) {
                return [\PHPStan\Analyser\InternalThrowPoint::createImplicit($scope, $propertyFetch)];
            }
            return [];
        }
        $getHook = $propertyReflection->getHook($hookName);
        $throwType = $getHook->getThrowType();
        if ($throwType !== null) {
            if (!$throwType->isVoid()->yes()) {
                return [\PHPStan\Analyser\InternalThrowPoint::createExplicit($scope, $throwType, $propertyFetch, \true)];
            }
        } elseif ($this->implicitThrows) {
            return [\PHPStan\Analyser\InternalThrowPoint::createImplicit($scope, $propertyFetch)];
        }
        return [];
    }
}
