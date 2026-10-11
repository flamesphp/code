<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ParametersProcessor.cpp')]
final class ParametersProcessor
{
    private \PHPStan\Analyser\AttributesHandler $attributesHandler;
    public function __construct(\PHPStan\Analyser\AttributesHandler $attributesHandler)
    {
        $this->attributesHandler = $attributesHandler;
    }
    /**
     * @param Node\Param[] $params
     * @param callable(Node $node, Scope $scope): void $nodeCallback
     */
    public function processParams(\PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, Node\Stmt $stmt, array $params, \PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\ExpressionResultStorage $storage, callable $nodeCallback): ?Dependencies
    {
        $dependencies = [];
        foreach ($params as $param) {
            $dependencies[] = $this->attributesHandler->processAttributeGroups($nodeScopeResolver, $stmt, $param->attrGroups, $scope, $storage, $nodeCallback);
            $nodeScopeResolver->callNodeCallback($nodeCallback, $param, $scope, $storage);
            if ($param->type !== null) {
                $nodeScopeResolver->callNodeCallback($nodeCallback, $param->type, $scope, $storage);
            }
            if ($param->default === null) {
                continue;
            }
            $dependencies[] = $nodeScopeResolver->processExprNode($stmt, $param->default, $scope, $storage, $nodeCallback, \PHPStan\Analyser\ExpressionContext::createDeep())->getDependencies();
        }
        return Dependencies::merge(...$dependencies);
    }
}
