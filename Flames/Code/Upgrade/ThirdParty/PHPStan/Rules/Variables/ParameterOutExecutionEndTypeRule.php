<?php

declare (strict_types=1);
namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeUtils;
/**
 * @implements Rule<ExecutionEndNode>
 */
#[RegisteredRule(3)]
final class ParameterOutExecutionEndTypeRule implements Rule
{
    private \PHPStan\Rules\Variables\ParameterOutTypeCheck $parameterOutTypeCheck;
    public function __construct(\PHPStan\Rules\Variables\ParameterOutTypeCheck $parameterOutTypeCheck)
    {
        $this->parameterOutTypeCheck = $parameterOutTypeCheck;
    }
    public function getNodeType(): string
    {
        return ExecutionEndNode::class;
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
        $endNode = $node->node;
        if ($endNode instanceof Node\Stmt\Expression) {
            $endNodeExpr = $endNode->expr;
            $endNodeExprType = $scope->getType($endNodeExpr);
            if ($endNodeExprType instanceof NeverType && $endNodeExprType->isExplicit()) {
                return [];
            }
        }
        $parameters = $inFunction->getParameters();
        $errors = [];
        foreach ($parameters as $parameter) {
            if (!$parameter->passedByReference()->createsNewVariable()) {
                continue;
            }
            foreach ($this->processSingleParameter($scope, $inFunction, $parameter) as $error) {
                $errors[] = $error;
            }
        }
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\ExtendedMethodReflection $inFunction
     */
    private function processSingleParameter(Scope $scope, $inFunction, ExtendedParameterReflection $parameter): array
    {
        $outType = $parameter->getOutType();
        if ($outType === null) {
            return [];
        }
        if ($scope->hasExpressionType(new ParameterVariableOriginalValueExpr($parameter->getName()))->no()) {
            return [];
        }
        $outType = TypeUtils::resolveLateResolvableTypes($outType);
        return $this->parameterOutTypeCheck->check($scope, $inFunction, $parameter, new Node\Expr\Variable($parameter->getName()), $outType, \true);
    }
}
