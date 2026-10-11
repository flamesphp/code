<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(6)]
#[ValidatesStubFiles]
final class MissingMethodReturnTypehintRule implements Rule
{
    private MissingTypehintCheck $missingTypehintCheck;
    public function __construct(MissingTypehintCheck $missingTypehintCheck)
    {
        $this->missingTypehintCheck = $missingTypehintCheck;
    }
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $methodReflection = $node->methodReflection;
        if ($scope->isInTrait()) {
            $methodNode = $node->originalNode;
            $originalMethodName = $methodNode->getAttribute('originalTraitMethodName');
            if ($originalMethodName === '__construct') {
                return [];
            }
        }
        $returnType = $methodReflection->getReturnType();
        if ($returnType instanceof MixedType && !$returnType->isExplicitMixed()) {
            return [RuleErrorBuilder::message(sprintf('Method %s::%s() has no return type specified.', $methodReflection->declaringClass->getDisplayName(), $methodReflection->getName()))->identifier('missingType.return')->build()];
        }
        $messages = [];
        foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($returnType) as $iterableTypeDescription) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() return type has no value type specified in %s.', $methodReflection->declaringClass->getDisplayName(), $methodReflection->getName(), $iterableTypeDescription))->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)->identifier('missingType.iterableValue')->build();
        }
        foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($returnType) as [$name, $genericTypeNames]) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() return type with generic %s does not specify its types: %s', $methodReflection->declaringClass->getDisplayName(), $methodReflection->getName(), $name, $genericTypeNames))->identifier('missingType.generics')->build();
        }
        foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($returnType) as $callableType) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() return type has no signature specified for %s.', $methodReflection->declaringClass->getDisplayName(), $methodReflection->getName(), $callableType->describe(VerbosityLevel::typeOnly())))->identifier('missingType.callable')->build();
        }
        return $messages;
    }
}
