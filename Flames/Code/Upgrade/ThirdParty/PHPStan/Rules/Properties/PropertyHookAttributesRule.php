<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<InPropertyHookNode>
 */
#[RegisteredRule(0)]
final class PropertyHookAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return InPropertyHookNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $attrGroups = $node->originalNode->attrGroups;
        $errors = $this->attributesCheck->check($scope, $attrGroups, Attribute::TARGET_METHOD, 'method');
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $name = $attribute->name->name;
                if (strtolower($name) === 'nodiscard') {
                    $errors[] = RuleErrorBuilder::message(sprintf('Attribute class %s cannot be used on property hooks.', $name))->identifier('attribute.target')->line($attribute->getStartLine())->nonIgnorable()->build();
                    break;
                }
            }
        }
        return $errors;
    }
}
