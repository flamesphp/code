<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use function count;
/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(2)]
#[ValidatesStubFiles]
final class MethodConditionalReturnTypeRule implements Rule
{
    private \PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper $helper;
    public function __construct(\PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper $helper)
    {
        $this->helper = $helper;
    }
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->methodReflection;
        $variants = $method->getVariants();
        if (count($variants) !== 1) {
            return [];
        }
        return $this->helper->check($variants[0]);
    }
}
