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
final class PropertyInClassRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->classReflection;
        if (!$classReflection->isClass()) {
            return [];
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::FINAL) && !$scope->getPhpVersion()->supportsFinalProperties()->yes()) {
            return [RuleErrorBuilder::message('Final properties are supported only on PHP 8.4 and later.')->nonIgnorable()->identifier('property.final')->build()];
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::ABSTRACT)) {
            if (!($node->getHooks() !== [])) {
                return [RuleErrorBuilder::message('Only hooked properties can be declared abstract.')->nonIgnorable()->identifier('property.abstractNonHooked')->build()];
            }
            if (!$this->isAtLeastOneHookBodyEmpty($node)) {
                return [RuleErrorBuilder::message('Abstract properties must specify at least one abstract hook.')->nonIgnorable()->identifier('property.abstractWithoutAbstractHook')->build()];
            }
            if (!$classReflection->isAbstract()) {
                return [RuleErrorBuilder::message('Non-abstract classes cannot include abstract properties.')->nonIgnorable()->identifier('property.abstract')->build()];
            }
        } elseif (!$this->doAllHooksHaveBody($node)) {
            return [RuleErrorBuilder::message('Non-abstract properties cannot include hooks without bodies.')->nonIgnorable()->identifier('property.hookWithoutBody')->build()];
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::PRIVATE)) {
            if ((bool) ($node->flags & \PhpParser\Modifiers::ABSTRACT)) {
                return [RuleErrorBuilder::message('Property cannot be both abstract and private.')->nonIgnorable()->identifier('property.abstractPrivate')->build()];
            }
            if ((bool) ($node->flags & \PhpParser\Modifiers::FINAL)) {
                return [RuleErrorBuilder::message('Property cannot be both final and private.')->nonIgnorable()->identifier('property.finalPrivate')->build()];
            }
            foreach ($node->getHooks() as $hook) {
                if (!(bool) ($hook->flags & \PhpParser\Modifiers::FINAL)) {
                    continue;
                }
                return [RuleErrorBuilder::message('Private property cannot have a final hook.')->nonIgnorable()->identifier('property.finalPrivateHook')->build()];
            }
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::ABSTRACT)) {
            if ((bool) ($node->flags & \PhpParser\Modifiers::FINAL)) {
                return [RuleErrorBuilder::message('Property cannot be both abstract and final.')->nonIgnorable()->identifier('property.abstractFinal')->build()];
            }
            foreach ($node->getHooks() as $hook) {
                if ($hook->body !== null) {
                    continue;
                }
                if (!(bool) ($hook->flags & \PhpParser\Modifiers::FINAL)) {
                    continue;
                }
                return [RuleErrorBuilder::message('Property cannot be both abstract and final.')->nonIgnorable()->identifier('property.abstractFinal')->build()];
            }
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::READONLY) || $node->isReadonlyClass) {
            if ($node->getHooks() !== []) {
                return [RuleErrorBuilder::message('Hooked properties cannot be readonly.')->nonIgnorable()->identifier('property.hookReadOnly')->build()];
            }
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::STATIC)) {
            if ($node->getHooks() !== []) {
                return [RuleErrorBuilder::message('Hooked properties cannot be static.')->nonIgnorable()->identifier('property.hookedStatic')->build()];
            }
            if (!$scope->getPhpVersion()->supportsAsymmetricVisibilityForStaticProperties()->yes() && ((bool) ($node->flags & \PhpParser\Modifiers::PRIVATE_SET) || (bool) ($node->flags & \PhpParser\Modifiers::PROTECTED_SET) || (bool) ($node->flags & \PhpParser\Modifiers::PUBLIC_SET))) {
                return [RuleErrorBuilder::message('Asymmetric visibility for static properties is supported only on PHP 8.5 and later.')->nonIgnorable()->identifier('property.staticAsymmetricVisibility')->build()];
            }
        }
        if ($node->isVirtual()) {
            if ($node->default !== null) {
                return [RuleErrorBuilder::message('Virtual hooked properties cannot have a default value.')->nonIgnorable()->identifier('property.virtualDefault')->build()];
            }
        }
        if (!$scope->getPhpVersion()->supportsPropertyHooks()->yes()) {
            if ($node->getHooks() !== []) {
                return [RuleErrorBuilder::message('Property hooks are supported only on PHP 8.4 and later.')->nonIgnorable()->identifier('property.hooksNotSupported')->build()];
            }
            return [];
        }
        return [];
    }
    private function doAllHooksHaveBody(ClassPropertyNode $node): bool
    {
        foreach ($node->getHooks() as $hook) {
            if ($hook->body === null) {
                return \false;
            }
        }
        return \true;
    }
    private function isAtLeastOneHookBodyEmpty(ClassPropertyNode $node): bool
    {
        foreach ($node->getHooks() as $hook) {
            if ($hook->body === null) {
                return \true;
            }
        }
        return \false;
    }
}
