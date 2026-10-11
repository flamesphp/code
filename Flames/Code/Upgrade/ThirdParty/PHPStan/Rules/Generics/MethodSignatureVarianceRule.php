<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(2)]
#[ValidatesStubFiles]
final class MethodSignatureVarianceRule implements Rule
{
    private \PHPStan\Rules\Generics\VarianceCheck $varianceCheck;
    public function __construct(\PHPStan\Rules\Generics\VarianceCheck $varianceCheck)
    {
        $this->varianceCheck = $varianceCheck;
    }
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->methodReflection;
        return $this->varianceCheck->checkParametersAcceptor($method, sprintf('in parameter %%s of method %s::%s()', SprintfHelper::escapeFormatString($method->declaringClass->getDisplayName()), SprintfHelper::escapeFormatString($method->getName())), sprintf('in param-out type of parameter %%s of method %s::%s()', SprintfHelper::escapeFormatString($method->declaringClass->getDisplayName()), SprintfHelper::escapeFormatString($method->getName())), sprintf('in return type of method %s::%s()', $method->declaringClass->getDisplayName(), $method->getName()), sprintf('in method %s::%s()', $method->declaringClass->getDisplayName(), $method->getName()), $method->isStatic(), $method->isPrivate() || $method->getName() === '__construct', 'method');
    }
}
