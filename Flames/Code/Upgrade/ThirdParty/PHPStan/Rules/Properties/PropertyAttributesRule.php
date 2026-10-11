<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(0)]
final class PropertyAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if (!$scope->getPhpVersion()->supportsOverrideAttributeOnProperty()->yes()) {
            $propertyReflection = $node->classReflection->getNativeProperty($node->name);
            if (count($propertyReflection->getNativeReflection()->getAttributes('Override')) > 0) {
                return [RuleErrorBuilder::message('Attribute class Override can be used with properties only on PHP 8.5 and later.')->identifier('property.overrideAttribute')->nonIgnorable()->build()];
            }
        }
        if ($node->isPromoted) {
            return [];
        }
        return $this->attributesCheck->check($scope, $node->getAttrGroups(), Attribute::TARGET_PROPERTY, 'property');
    }
}
