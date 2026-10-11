<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<InFunctionNode>
 */
#[RegisteredRule(2)]
#[ValidatesStubFiles]
final class FunctionSignatureVarianceRule implements Rule
{
    private \PHPStan\Rules\Generics\VarianceCheck $varianceCheck;
    public function __construct(\PHPStan\Rules\Generics\VarianceCheck $varianceCheck)
    {
        $this->varianceCheck = $varianceCheck;
    }
    public function getNodeType(): string
    {
        return InFunctionNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $functionReflection = $node->functionReflection;
        $functionName = $functionReflection->getName();
        return $this->varianceCheck->checkParametersAcceptor($functionReflection, sprintf('in parameter %%s of function %s()', SprintfHelper::escapeFormatString($functionName)), sprintf('in param-out type of parameter %%s of function %s()', SprintfHelper::escapeFormatString($functionName)), sprintf('in return type of function %s()', $functionName), sprintf('in function %s()', $functionName), \false, \false, 'function');
    }
}
