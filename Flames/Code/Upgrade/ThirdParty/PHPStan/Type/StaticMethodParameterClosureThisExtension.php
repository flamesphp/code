<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
/**
 * This is the interface for dynamically specifying the $this context
 * for closure parameters in static method calls.
 *
 * To register it in the configuration file use the `phpstan.staticMethodParameterClosureThisExtension` service tag:
 *
 * ```
 * services:
 * 	-
 * 		class: App\PHPStan\MyExtension
 * 		tags:
 * 			- phpstan.staticMethodParameterClosureThisExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface('phpstan.staticMethodParameterClosureThisExtension')]
interface StaticMethodParameterClosureThisExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getClosureThisTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type;
}
