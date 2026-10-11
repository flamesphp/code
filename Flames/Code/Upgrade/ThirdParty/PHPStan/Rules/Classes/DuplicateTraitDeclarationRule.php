<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InTraitNode>
 */
#[RegisteredRule(0)]
final class DuplicateTraitDeclarationRule implements Rule
{
    private \PHPStan\Rules\Classes\DuplicateDeclarationHelper $helper;
    public function __construct(\PHPStan\Rules\Classes\DuplicateDeclarationHelper $helper)
    {
        $this->helper = $helper;
    }
    public function getNodeType(): string
    {
        return InTraitNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->helper->checkClassLike($node->originalNode, $node->traitReflection->getDisplayName(), 'trait');
    }
}
