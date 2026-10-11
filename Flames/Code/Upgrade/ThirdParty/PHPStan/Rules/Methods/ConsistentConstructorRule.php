<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Classes\ConsistentConstructorHelper;
use PHPStan\Rules\Rule;
use function array_merge;
use function strtolower;
/** @implements Rule<InClassMethodNode> */
#[RegisteredRule(0)]
final class ConsistentConstructorRule implements Rule
{
    private ConsistentConstructorHelper $consistentConstructorHelper;
    private \PHPStan\Rules\Methods\MethodParameterComparisonHelper $methodParameterComparisonHelper;
    private \PHPStan\Rules\Methods\MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper;
    public function __construct(ConsistentConstructorHelper $consistentConstructorHelper, \PHPStan\Rules\Methods\MethodParameterComparisonHelper $methodParameterComparisonHelper, \PHPStan\Rules\Methods\MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper)
    {
        $this->consistentConstructorHelper = $consistentConstructorHelper;
        $this->methodParameterComparisonHelper = $methodParameterComparisonHelper;
        $this->methodVisibilityComparisonHelper = $methodVisibilityComparisonHelper;
    }
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $method = $node->methodReflection;
        if (strtolower($method->getName()) !== '__construct') {
            return [];
        }
        $parent = $method->declaringClass->getParentClass();
        if ($parent === null) {
            return [];
        }
        $parentConstructor = $this->consistentConstructorHelper->findConsistentConstructor($parent);
        if ($parentConstructor === null) {
            return [];
        }
        return array_merge($this->methodParameterComparisonHelper->compare($parentConstructor, $parentConstructor->getDeclaringClass(), $method, $scope, \true), $this->methodVisibilityComparisonHelper->compare($parentConstructor, $parentConstructor->getDeclaringClass(), $method, $node->originalNode));
    }
}
