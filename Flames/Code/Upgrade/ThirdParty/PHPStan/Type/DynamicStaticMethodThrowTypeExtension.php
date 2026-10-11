<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
/**
 * This is the interface dynamic throw type extensions implement for static methods.
 *
 * To register it in the configuration file use the `phpstan.dynamicStaticMethodThrowTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicStaticMethodThrowTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-throw-type-extensions
 *
 * @api
 */
#[ExtensionInterface('phpstan.dynamicStaticMethodThrowTypeExtension')]
interface DynamicStaticMethodThrowTypeExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool;
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type;
}
