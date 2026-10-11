<?php

declare (strict_types=1);
namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeUtils;
use function is_string;
/**
 * @implements Rule<VariableAssignNode>
 */
#[RegisteredRule(3)]
final class ParameterOutAssignedTypeRule implements Rule
{
    private \PHPStan\Rules\Variables\ParameterOutTypeCheck $parameterOutTypeCheck;
    public function __construct(\PHPStan\Rules\Variables\ParameterOutTypeCheck $parameterOutTypeCheck)
    {
        $this->parameterOutTypeCheck = $parameterOutTypeCheck;
    }
    public function getNodeType(): string
    {
        return VariableAssignNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $inFunction = $scope->getFunction();
        if ($inFunction === null) {
            return [];
        }
        if ($scope->isInAnonymousFunction()) {
            return [];
        }
        $variable = $node->variable;
        if (!is_string($variable->name)) {
            return [];
        }
        $parameters = $inFunction->getParameters();
        $foundParameter = null;
        foreach ($parameters as $parameter) {
            if (!$parameter->passedByReference()->createsNewVariable()) {
                continue;
            }
            if ($parameter->getName() !== $variable->name) {
                continue;
            }
            $foundParameter = $parameter;
            break;
        }
        if ($foundParameter === null) {
            return [];
        }
        $isParamOutType = \true;
        $outType = $foundParameter->getOutType();
        if ($outType === null) {
            $isParamOutType = \false;
            $outType = $foundParameter->getType();
        }
        $outType = TypeUtils::resolveLateResolvableTypes($outType);
        return $this->parameterOutTypeCheck->check($scope, $inFunction, $foundParameter, $node->assignedExpr, $outType, $isParamOutType);
    }
}
