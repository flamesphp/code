<?php

declare (strict_types=1);
namespace PHPStan\Rules\Traits;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
/**
 * @implements Rule<InTraitNode>
 */
#[RegisteredRule(0)]
final class TraitAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return InTraitNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if (!$scope->getPhpVersion()->supportsDeprecatedTraits()->yes()) {
            if (count($node->traitReflection->getNativeReflection()->getAttributes('Deprecated')) > 0) {
                return [RuleErrorBuilder::message('Attribute class Deprecated can be used with traits only on PHP 8.5 and later.')->identifier('trait.deprecatedAttribute')->nonIgnorable()->build()];
            }
        }
        $originalNode = $node->originalNode;
        $errors = $this->attributesCheck->check($scope, $originalNode->attrGroups, Attribute::TARGET_CLASS, 'class');
        if (count($node->traitReflection->getNativeReflection()->getAttributes('AllowDynamicProperties')) > 0) {
            $errors[] = RuleErrorBuilder::message('Attribute class AllowDynamicProperties cannot be used with trait.')->identifier('trait.allowDynamicProperties')->nonIgnorable()->build();
        }
        return $errors;
    }
}
