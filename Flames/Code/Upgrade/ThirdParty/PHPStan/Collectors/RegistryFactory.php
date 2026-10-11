<?php

declare (strict_types=1);
namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
#[AutowiredService]
final class RegistryFactory
{
    /**
     * @var ExtensionsCollection<Collector<Node, mixed>>
     */
    private ExtensionsCollection $collectors;
    public const COLLECTOR_TAG = 'phpstan.collector';
    /**
     * @param ExtensionsCollection<Collector<Node, mixed>> $collectors
     */
    public function __construct(
        #[AutowiredExtensions(\PHPStan\Collectors\Collector::class)]
        ExtensionsCollection $collectors
    )
    {
        $this->collectors = $collectors;
    }
    public function create(): \PHPStan\Collectors\Registry
    {
        return new \PHPStan\Collectors\Registry($this->collectors->getAll());
    }
}
