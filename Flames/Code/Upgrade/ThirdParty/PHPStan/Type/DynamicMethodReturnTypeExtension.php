<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
/**
 * This is the interface dynamic return type extensions implement for non-static methods.
 *
 * To register it in the configuration file use the `phpstan.broker.dynamicMethodReturnTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.dynamicMethodReturnTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-return-type-extensions
 *
 * @api
 */
#[ExtensionInterface(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG)]
interface DynamicMethodReturnTypeExtension
{
    /** @return class-string */
    public function getClass(): string;
    public function isMethodSupported(MethodReflection $methodReflection): bool;
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type;
}
