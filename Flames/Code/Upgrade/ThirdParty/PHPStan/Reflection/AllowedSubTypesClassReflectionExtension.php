<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Type\Type;
/**
 * This is the extension interface to implement if you want to described
 * allowed subtypes - to limit which classes can implement a certain interface
 * or extend a certain parent class.
 *
 * To register it in the configuration file use the `phpstan.broker.allowedSubTypesClassReflectionExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.allowedSubTypesClassReflectionExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/allowed-subtypes
 *
 * @api
 */
#[ExtensionInterface(BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG)]
interface AllowedSubTypesClassReflectionExtension
{
    public function supports(\PHPStan\Reflection\ClassReflection $classReflection): bool;
    /**
     * @return array<Type>
     */
    public function getAllowedSubTypes(\PHPStan\Reflection\ClassReflection $classReflection): array;
}
