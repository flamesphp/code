<?php

declare (strict_types=1);
namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\UnionType;
/**
 * @implements Rule<InArrowFunctionNode>
 */
#[RegisteredRule(4)]
final class TooWideArrowFunctionReturnTypehintRule implements Rule
{
    private \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check;
    public function __construct(\PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return InArrowFunctionNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $arrowFunction = $node->originalNode;
        if ($arrowFunction->returnType === null) {
            return [];
        }
        $expr = $arrowFunction->expr;
        if ($expr instanceof Node\Expr\YieldFrom || $expr instanceof Node\Expr\Yield_) {
            return [];
        }
        $functionReturnType = $scope->getFunctionType($arrowFunction->returnType, \false, \false);
        if (!$functionReturnType instanceof UnionType) {
            return [];
        }
        return $this->check->checkAnonymousFunction($scope->getType($expr), $functionReturnType);
    }
}
