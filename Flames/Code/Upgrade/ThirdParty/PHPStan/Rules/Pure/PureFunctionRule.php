<?php

declare (strict_types=1);
namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(2)]
final class PureFunctionRule implements Rule
{
    private \PHPStan\Rules\Pure\FunctionPurityCheck $check;
    public function __construct(\PHPStan\Rules\Pure\FunctionPurityCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return FunctionReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $function = $node->functionReflection;
        return $this->check->check($scope, sprintf('Function %s()', $function->getName()), 'Function', $function, $function->getParameters(), $function->getReturnType(), $node->impurePoints, $node->statementResult->getThrowPoints(), $node->function->getStmts(), \false);
    }
}
