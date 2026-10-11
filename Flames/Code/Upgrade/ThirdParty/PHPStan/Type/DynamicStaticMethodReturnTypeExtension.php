<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
/**
 * This is the interface dynamic return type extensions implement for static methods.
 *
 * To register it in the configuration file use the `phpstan.broker.dynamicStaticMethodReturnTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.dynamicStaticMethodReturnTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-return-type-extensions
 *
 * @api
 */
#[ExtensionInterface(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG)]
interface DynamicStaticMethodReturnTypeExtension
{
    /** @return class-string */
    public function getClass(): string;
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool;
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, \PHPStan\Analyser\Scope $scope): ?\PHPStan\Type\Type;
}
