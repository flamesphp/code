<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use function sprintf;
/**
 * @implements Rule<PropertyAssignNode>
 */
#[RegisteredRule(0)]
final class WritingToReadOnlyPropertiesRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    private \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor;
    private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;
    private bool $checkThisOnly;
    public function __construct(
        RuleLevelHelper $ruleLevelHelper,
        \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor,
        \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder,
        #[AutowiredParameter]
        bool $checkThisOnly
    )
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->propertyDescriptor = $propertyDescriptor;
        $this->propertyReflectionFinder = $propertyReflectionFinder;
        $this->checkThisOnly = $checkThisOnly;
    }
    public function getNodeType(): string
    {
        return PropertyAssignNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $propertyFetch = $node->propertyFetch;
        if ($propertyFetch instanceof Node\Expr\PropertyFetch && $this->checkThisOnly && !$this->ruleLevelHelper->isThis($propertyFetch->var)) {
            return [];
        }
        $propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($propertyFetch, $scope);
        if ($propertyReflection === null) {
            return [];
        }
        if (!$scope->canWriteProperty($propertyReflection)) {
            return [];
        }
        if (!$propertyReflection->originalPropertyReflection->isWritable()) {
            $propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $scope, $propertyFetch);
            return [RuleErrorBuilder::message(sprintf('%s is not writable.', $propertyDescription))->line($propertyFetch->name->getStartLine())->identifier('assign.propertyReadOnly')->build()];
        }
        return [];
    }
}
