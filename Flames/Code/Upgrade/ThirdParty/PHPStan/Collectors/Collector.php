<?php

declare (strict_types=1);
namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
/**
 * This is the interface custom collectors implement. To register it in the configuration file
 * use the `phpstan.collector` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\MyCollector
 *		tags:
 *			- phpstan.collector
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/collectors
 *
 * @api
 * @template-covariant TNodeType of Node
 * @template-covariant TValue
 */
#[ExtensionInterface(\PHPStan\Collectors\RegistryFactory::COLLECTOR_TAG)]
interface Collector
{
    /**
     * @return class-string<TNodeType>
     */
    public function getNodeType(): string;
    /**
     * @param TNodeType $node
     * @return TValue|null Collected data
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope);
}
