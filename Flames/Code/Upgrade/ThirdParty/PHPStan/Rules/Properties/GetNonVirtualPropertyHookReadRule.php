<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;
/**
 * @implements Rule<ClassPropertiesNode>
 */
#[RegisteredRule(3)]
final class GetNonVirtualPropertyHookReadRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $reads = [];
        $classReflection = $node->classReflection;
        foreach ($node->propertyUsages as $propertyUsage) {
            if (!$propertyUsage instanceof PropertyRead) {
                continue;
            }
            $fetch = $propertyUsage->fetch;
            if (!$fetch instanceof Node\Expr\PropertyFetch) {
                continue;
            }
            if (!$fetch->name instanceof Node\Identifier) {
                continue;
            }
            $propertyName = $fetch->name->name;
            if (!$fetch->var instanceof Node\Expr\Variable || $fetch->var->name !== 'this') {
                continue;
            }
            $usageScope = $propertyUsage->scope;
            $inFunction = $usageScope->getFunction();
            if (!$inFunction instanceof PhpMethodFromParserNodeReflection) {
                continue;
            }
            if (!($inFunction->hookForProperty !== null)) {
                continue;
            }
            if ($inFunction->getPropertyHookName() !== 'get') {
                continue;
            }
            if ($propertyName !== $inFunction->hookForProperty) {
                continue;
            }
            $reads[$propertyName] = \true;
        }
        $errors = [];
        foreach ($node->properties as $propertyNode) {
            $hasGetHook = \false;
            foreach ($propertyNode->getHooks() as $hook) {
                if (strtolower($hook->name->name) !== 'get') {
                    continue;
                }
                if ($hook->body === null) {
                    continue;
                }
                $hasGetHook = \true;
                break;
            }
            if (!$hasGetHook) {
                continue;
            }
            if (array_key_exists($propertyNode->name, $reads)) {
                continue;
            }
            $propertyReflection = $classReflection->getNativeProperty($propertyNode->name);
            if ($propertyReflection->isVirtual()->yes()) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf('Get hook for non-virtual property %s::$%s does not read its value.', $classReflection->getDisplayName(), $propertyNode->name))->line($this->getGetHookLine($propertyNode))->identifier('propertyGetHook.noRead')->build();
        }
        return $errors;
    }
    private function getGetHookLine(ClassPropertyNode $propertyNode): int
    {
        $getHook = null;
        foreach ($propertyNode->getHooks() as $hook) {
            if (strtolower($hook->name->name) !== 'get') {
                continue;
            }
            $getHook = $hook;
            break;
        }
        if ($getHook === null) {
            return $propertyNode->getStartLine();
        }
        return $getHook->getStartLine();
    }
}
