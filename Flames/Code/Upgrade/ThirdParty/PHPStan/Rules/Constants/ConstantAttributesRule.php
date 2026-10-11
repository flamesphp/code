<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use const PHP_VERSION_ID;
/**
 * @implements Rule<Node\Stmt\Const_>
 */
#[RegisteredRule(0)]
final class ConstantAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\Const_::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        if ($node->attrGroups === []) {
            return [];
        }
        if (!$scope->getPhpVersion()->supportsAttributesOnGlobalConstants()->yes()) {
            return [RuleErrorBuilder::message('Attributes on global constants are supported only on PHP 8.5 and later.')->identifier('constant.attributesNotSupported')->nonIgnorable()->build()];
        }
        if (PHP_VERSION_ID < 80500) {
            // because of Attribute::TARGET_CONSTANT constant
            return [RuleErrorBuilder::message('ConstantAttributesRule requires PHP 8.5 runtime to check the code.')->identifier('constant.attributesRuleCannotRun')->nonIgnorable()->build()];
        }
        return $this->attributesCheck->check($scope, $node->attrGroups, Attribute::TARGET_CONSTANT, 'constant');
    }
}
