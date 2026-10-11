<?php

declare (strict_types=1);
namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(2)]
final class PureMethodRule implements Rule
{
    private \PHPStan\Rules\Pure\FunctionPurityCheck $check;
    public function __construct(\PHPStan\Rules\Pure\FunctionPurityCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->methodReflection;
        return $this->check->check($scope, sprintf('Method %s::%s()', $method->declaringClass->getDisplayName(), $method->getName()), 'Method', $method, $method->getParameters(), $method->getReturnType(), $node->impurePoints, $node->statementResult->getThrowPoints(), $node->getStatements(), $method->isConstructor);
    }
}
