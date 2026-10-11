<?php

declare (strict_types=1);
namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function ucfirst;
/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
#[RegisteredRule(2)]
final class PurePropertyHookRule implements Rule
{
    private \PHPStan\Rules\Pure\FunctionPurityCheck $check;
    public function __construct(\PHPStan\Rules\Pure\FunctionPurityCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return PropertyHookReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $hookReflection = $node->hookReflection;
        $hookName = $hookReflection->getPropertyHookName();
        if ($hookName === null) {
            throw new ShouldNotHappenException();
        }
        return $this->check->check($scope, sprintf('%s hook for property %s::$%s', ucfirst($hookName), $hookReflection->declaringClass->getDisplayName(), $hookReflection->hookForProperty), 'PropertyHook', $hookReflection, $hookReflection->getParameters(), $hookReflection->getReturnType(), $node->impurePoints, $node->statementResult->getThrowPoints(), $node->hook->getStmts() ?? [], \false);
    }
}
