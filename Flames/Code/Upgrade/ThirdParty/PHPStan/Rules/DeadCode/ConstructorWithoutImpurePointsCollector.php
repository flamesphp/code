<?php

declare (strict_types=1);
namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Node\MethodReturnStatementsNode;
use function count;
/**
 * @implements Collector<MethodReturnStatementsNode, array{string, list<string>}>
 */
#[RegisteredCollector(4)]
final class ConstructorWithoutImpurePointsCollector implements Collector
{
    private \PHPStan\Rules\DeadCode\PossiblyPureCallTransitivePurityResolver $purityResolver;
    public function __construct(\PHPStan\Rules\DeadCode\PossiblyPureCallTransitivePurityResolver $purityResolver)
    {
        $this->purityResolver = $purityResolver;
    }
    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope)
    {
        $method = $node->methodReflection;
        if (!$method->isConstructor) {
            return null;
        }
        if (!$method->isPure()->maybe()) {
            return null;
        }
        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->passedByReference()->createsNewVariable()) {
                continue;
            }
            return null;
        }
        if (count($method->getAsserts()->getAll()) !== 0) {
            return null;
        }
        $throwType = $method->getThrowType();
        if ($throwType !== null && !$throwType->isVoid()->yes()) {
            return null;
        }
        $dependencies = $this->purityResolver->resolveDependencies($node->impurePoints, $node->statementResult->getThrowPoints());
        if ($dependencies === null) {
            return null;
        }
        return [$method->declaringClass->getName(), $dependencies];
    }
}
