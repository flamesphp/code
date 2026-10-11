<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
/**
 * This is the interface for dynamically specifying the $this context
 * for closure parameters in method calls.
 *
 * To register it in the configuration file use the `phpstan.methodParameterClosureThisExtension` service tag:
 *
 * ```
 * services:
 * 	-
 * 		class: App\PHPStan\MyExtension
 * 		tags:
 * 			- phpstan.methodParameterClosureThisExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface('phpstan.methodParameterClosureThisExtension')]
interface MethodParameterClosureThisExtension
{
    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getClosureThisTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type;
}
