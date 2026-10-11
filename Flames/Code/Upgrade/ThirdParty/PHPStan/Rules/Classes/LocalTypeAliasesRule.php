<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(0)]
#[ValidatesStubFiles]
final class LocalTypeAliasesRule implements Rule
{
    private \PHPStan\Rules\Classes\LocalTypeAliasesCheck $check;
    public function __construct(\PHPStan\Rules\Classes\LocalTypeAliasesCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->check->check($scope, $node->classReflection, $node->originalNode);
    }
}
