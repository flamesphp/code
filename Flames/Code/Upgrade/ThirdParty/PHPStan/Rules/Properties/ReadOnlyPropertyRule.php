<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(0)]
final class ReadOnlyPropertyRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!((bool) ($node->flags & \PhpParser\Modifiers::READONLY) || $node->isReadonlyClass)) {
            return [];
        }
        $errors = [];
        if (!$scope->getPhpVersion()->supportsReadOnlyProperties()->yes()) {
            $errors[] = RuleErrorBuilder::message('Readonly properties are supported only on PHP 8.1 and later.')->nonIgnorable()->identifier('property.readOnlyNotSupported')->build();
        }
        if ($node->type === null) {
            $errors[] = RuleErrorBuilder::message('Readonly property must have a native type.')->identifier('property.readOnlyNoNativeType')->nonIgnorable()->build();
        }
        if ($node->default !== null) {
            $errors[] = RuleErrorBuilder::message('Readonly property cannot have a default value.')->nonIgnorable()->identifier('property.readOnlyDefaultValue')->build();
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::STATIC)) {
            $errors[] = RuleErrorBuilder::message('Readonly property cannot be static.')->nonIgnorable()->identifier('property.readOnlyStatic')->build();
        }
        return $errors;
    }
}
