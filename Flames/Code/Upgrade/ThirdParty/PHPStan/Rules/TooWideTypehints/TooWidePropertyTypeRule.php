<?php

declare (strict_types=1);
namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use function sprintf;
/**
 * @implements Rule<ClassPropertiesNode>
 */
#[RegisteredRule(4)]
final class TooWidePropertyTypeRule implements Rule
{
    /**
     * @var ExtensionsCollection<ReadWritePropertiesExtension>
     */
    private ExtensionsCollection $extensions;
    private \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check;
    /**
     * @param ExtensionsCollection<ReadWritePropertiesExtension> $extensions
     */
    public function __construct(
        #[AutowiredExtensions(ReadWritePropertiesExtension::class)]
        ExtensionsCollection $extensions,
        \PHPStan\Rules\TooWideTypehints\TooWideTypeCheck $check
    )
    {
        $this->extensions = $extensions;
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        $classReflection = $node->classReflection;
        foreach ($node->properties as $property) {
            if (!(bool) ($property->flags & \PhpParser\Modifiers::PRIVATE)) {
                continue;
            }
            if ($property->isDeclaredInTrait) {
                continue;
            }
            if ($property->isPromoted) {
                continue;
            }
            $propertyName = $property->name;
            if (!$classReflection->hasNativeProperty($propertyName)) {
                continue;
            }
            $propertyReflection = $classReflection->getNativeProperty($propertyName);
            foreach ($this->extensions->getAll() as $extension) {
                if ($extension->isAlwaysRead($propertyReflection, $propertyName)) {
                    continue 2;
                }
                if ($extension->isAlwaysWritten($propertyReflection, $propertyName)) {
                    continue 2;
                }
                if ($extension->isInitialized($propertyReflection, $propertyName)) {
                    continue 2;
                }
            }
            $propertyDescription = $this->describePropertyByName($propertyReflection, $propertyName);
            $propertyErrors = $this->check->checkProperty($property, $propertyReflection->getDeclaringClass(), $node->propertyAssigns, $propertyReflection->getNativeType(), $propertyReflection->getPhpDocType(), $propertyDescription, $scope);
            foreach ($propertyErrors as $error) {
                $errors[] = $error;
            }
        }
        return $errors;
    }
    private function describePropertyByName(PropertyReflection $property, string $propertyName): string
    {
        if (!$property->isStatic()) {
            return sprintf('Property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
        }
        return sprintf('Static property %s::$%s', $property->getDeclaringClass()->getDisplayName(), $propertyName);
    }
}
