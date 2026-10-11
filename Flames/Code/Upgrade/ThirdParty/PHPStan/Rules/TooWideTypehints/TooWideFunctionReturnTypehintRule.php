<?php

declare (strict_types=1);
namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(4)]
final class TooWideFunctionReturnTypehintRule implements Rule
{
    private \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check;
    public function __construct(\PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check)
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
        return $this->check->checkFunctionReturnType($node, $function->getNativeReturnType(), $function->getPhpDocReturnType(), sprintf('Function %s()', $function->getName()), \false, $scope);
    }
}
