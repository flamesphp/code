<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function sprintf;
/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(2)]
#[ValidatesStubFiles]
final class PropertyVarianceRule implements Rule
{
    private \PHPStan\Rules\Generics\VarianceCheck $varianceCheck;
    public function __construct(\PHPStan\Rules\Generics\VarianceCheck $varianceCheck)
    {
        $this->varianceCheck = $varianceCheck;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->classReflection;
        if (!$classReflection->hasNativeProperty($node->name)) {
            return [];
        }
        $propertyReflection = $classReflection->getNativeProperty($node->name);
        if ($propertyReflection->isPrivate()) {
            return [];
        }
        $variance = $this->hasRestrictedWriteAccess($node) ? TemplateTypeVariance::createCovariant() : TemplateTypeVariance::createInvariant();
        return $this->varianceCheck->check($variance, $propertyReflection->getReadableType(), sprintf('in property %s::$%s', SprintfHelper::escapeFormatString($classReflection->getDisplayName()), SprintfHelper::escapeFormatString($node->name)));
    }
    private function hasRestrictedWriteAccess(ClassPropertyNode $node): bool
    {
        if ((bool) ($node->flags & \PhpParser\Modifiers::READONLY) || $node->isReadonlyClass || $node->isReadonlyByPhpDoc) {
            return \true;
        }
        if ((bool) ($node->flags & \PhpParser\Modifiers::PRIVATE_SET)) {
            return \true;
        }
        $hooks = $node->getHooks();
        if ($hooks === []) {
            return \false;
        }
        foreach ($hooks as $hook) {
            if ($hook->name->name === 'set') {
                return \false;
            }
        }
        return \true;
    }
}
