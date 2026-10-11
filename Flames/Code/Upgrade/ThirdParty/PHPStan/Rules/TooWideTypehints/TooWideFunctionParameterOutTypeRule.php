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
final class TooWideFunctionParameterOutTypeRule implements Rule
{
    private \PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck $check;
    public function __construct(\PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return FunctionReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $inFunction = $node->functionReflection;
        return $this->check->check($node->getStartLine(), $node->executionEnds, $node->returnStatements, $inFunction->getParameters(), sprintf('Function %s()', $inFunction->getName()));
    }
}
