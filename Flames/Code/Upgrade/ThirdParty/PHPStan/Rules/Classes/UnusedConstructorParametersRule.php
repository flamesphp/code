<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;
/**
 * @implements Rule<VariableWritesNode>
 */
#[RegisteredRule(1)]
final class UnusedConstructorParametersRule implements Rule
{
    private UnusedParametersCheck $check;
    private bool $reportExactLine;
    private bool $reportUnusedFlow;
    public function __construct(
        UnusedParametersCheck $check,
        #[AutowiredParameter('%featureToggles.reportPreciseLineForUnusedFunctionParameter%')]
        bool $reportExactLine,
        #[AutowiredParameter('%featureToggles.unusedParameters%')]
        bool $reportUnusedFlow
    )
    {
        $this->check = $check;
        $this->reportExactLine = $reportExactLine;
        $this->reportUnusedFlow = $reportUnusedFlow;
    }
    public function getNodeType(): string
    {
        return VariableWritesNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $originalNode = $node->functionLike;
        if (!$originalNode instanceof Node\Stmt\ClassMethod) {
            return [];
        }
        if (strtolower($originalNode->name->name) !== '__construct' || $originalNode->stmts === null) {
            return [];
        }
        if (count($originalNode->params) === 0) {
            return [];
        }
        $method = $scope->getFunction();
        if ($method === null) {
            return [];
        }
        if ($node->opaque) {
            return [];
        }
        if (!$scope->isInClass()) {
            return [];
        }
        $classReflection = $scope->getClassReflection();
        if ($classReflection->isAttributeClass()) {
            return [];
        }
        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasConstructor()) {
                return [];
            }
        }
        $constructorDescription = sprintf('Constructor of class %s', SprintfHelper::escapeFormatString($classReflection->getDisplayName()));
        if ($classReflection->isAnonymous()) {
            $constructorDescription = 'Constructor of an anonymous class';
        }
        return $this->check->getUnusedParameterErrors($node, $method, $originalNode->params, sprintf('%s has an unused parameter $%%s.', $constructorDescription), 'constructor.unusedParameter', $this->reportExactLine, $this->reportUnusedFlow ? sprintf('%s has a parameter $%%s that only flows into values that are never used.', $constructorDescription) : null, $this->reportUnusedFlow ? 'constructor.unusedParameterFlow' : null);
    }
}
