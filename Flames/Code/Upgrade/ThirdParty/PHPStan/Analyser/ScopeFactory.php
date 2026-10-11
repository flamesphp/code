<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
/**
 * @api
 */
#[AutowiredService]
final class ScopeFactory
{
    private \PHPStan\Analyser\InternalScopeFactoryFactory $internalScopeFactoryFactory;
    public function __construct(\PHPStan\Analyser\InternalScopeFactoryFactory $internalScopeFactoryFactory)
    {
        $this->internalScopeFactoryFactory = $internalScopeFactoryFactory;
    }
    /**
     * @param callable(Node $node, Scope $scope): void $nodeCallback
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, ?callable $nodeCallback = null): \PHPStan\Analyser\MutatingScope
    {
        return $this->internalScopeFactoryFactory->create($nodeCallback)->create($context);
    }
}
