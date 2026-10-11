<?php

declare (strict_types=1);
namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use function array_key_exists;
use function array_keys;
use function array_map;
use function count;
use function is_string;
use function lcfirst;
use function sprintf;
use function str_contains;
/**
 * @implements Rule<ClassPropertiesNode>
 */
#[RegisteredRule(4)]
final class UnusedPrivatePropertyRule implements Rule
{
    /**
     * @var ExtensionsCollection<ReadWritePropertiesExtension>
     */
    private ExtensionsCollection $extensions;
    /**
     * @var string[]
     */
    private array $alwaysWrittenTags;
    /**
     * @var string[]
     */
    private array $alwaysReadTags;
    private bool $checkUninitializedProperties;
    /**
     * @param ExtensionsCollection<ReadWritePropertiesExtension> $extensions
     * @param string[] $alwaysWrittenTags
     * @param string[] $alwaysReadTags
     */
    public function __construct(
        #[AutowiredExtensions(ReadWritePropertiesExtension::class)]
        ExtensionsCollection $extensions,
        #[AutowiredParameter('%propertyAlwaysWrittenTags%')]
        array $alwaysWrittenTags,
        #[AutowiredParameter('%propertyAlwaysReadTags%')]
        array $alwaysReadTags,
        #[AutowiredParameter]
        bool $checkUninitializedProperties
    )
    {
        $this->extensions = $extensions;
        $this->alwaysWrittenTags = $alwaysWrittenTags;
        $this->alwaysReadTags = $alwaysReadTags;
        $this->checkUninitializedProperties = $checkUninitializedProperties;
    }
    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Stmt\Class_) {
            return [];
        }
        $classReflection = $node->classReflection;
        $classType = new ObjectType($classReflection->getName(), \null, $classReflection);
        // A property node declared in a trait only reaches us when NodeScopeResolver traversed
        // that trait's body, which it does for analysed files only. Its presence therefore
        // proves the trait's own usages are visible.
        $propertyNamesDeclaredInTraitBody = [];
        foreach ($node->properties as $property) {
            if (!$property->isDeclaredInTrait) {
                continue;
            }
            $propertyNamesDeclaredInTraitBody[$property->name] = \true;
        }
        $properties = [];
        foreach ($node->properties as $property) {
            if (!(bool) ($property->flags & \PhpParser\Modifiers::PRIVATE)) {
                continue;
            }
            if ($property->isDeclaredInTrait) {
                continue;
            }
            if (!array_key_exists($property->name, $propertyNamesDeclaredInTraitBody) && $this->isRedeclaringPrivateTraitProperty($classReflection, $property->name)) {
                continue;
            }
            $alwaysRead = !$property->isReadable();
            $alwaysWritten = !$property->isWritable();
            if ($property->phpDoc !== null) {
                $text = $property->phpDoc;
                foreach ($this->alwaysReadTags as $tag) {
                    if (!str_contains($text, $tag)) {
                        continue;
                    }
                    $alwaysRead = \true;
                    break;
                }
                foreach ($this->alwaysWrittenTags as $tag) {
                    if (!str_contains($text, $tag)) {
                        continue;
                    }
                    $alwaysWritten = \true;
                    break;
                }
            }
            $propertyName = $property->name;
            if (!$alwaysRead || !$alwaysWritten) {
                if (!$classReflection->hasNativeProperty($propertyName)) {
                    continue;
                }
                $propertyReflection = $classReflection->getNativeProperty($propertyName);
                foreach ($this->extensions->getAll() as $extension) {
                    if ($alwaysRead && $alwaysWritten) {
                        break;
                    }
                    if (!$alwaysRead && $extension->isAlwaysRead($propertyReflection, $propertyName)) {
                        $alwaysRead = \true;
                    }
                    if ($alwaysWritten || !$extension->isAlwaysWritten($propertyReflection, $propertyName)) {
                        continue;
                    }
                    $alwaysWritten = \true;
                }
            }
            $read = $alwaysRead;
            $written = $alwaysWritten || $property->default !== null;
            $properties[$propertyName] = ['read' => $read, 'written' => $written, 'node' => $property, 'onlyReadable' => $property->isReadable() && !$property->isWritable(), 'onlyWritable' => $property->isWritable() && !$property->isReadable(), 'hasTrueRead' => $alwaysRead];
        }
        foreach ($node->propertyUsages as $usage) {
            $usageScope = $usage->getScope();
            $fetch = $usage->getFetch();
            if ($fetch->name instanceof Node\Identifier) {
                $propertyName = $fetch->name->name;
                $propertyNames = [$propertyName];
                if ($usageScope->getFunction() !== null && $fetch instanceof Node\Expr\PropertyFetch && $fetch->var instanceof Node\Expr\Variable && is_string($fetch->var->name) && $fetch->var->name === 'this') {
                    $methodReflection = $usageScope->getFunction();
                    if ($methodReflection instanceof PhpMethodFromParserNodeReflection && $methodReflection->hookForProperty !== null && $methodReflection->hookForProperty === $propertyName && ($methodReflection->getPropertyHookName() === 'set' || $usage instanceof PropertyRead)) {
                        continue;
                    }
                }
            } else {
                $propertyNameType = $usageScope->getType($fetch->name);
                $strings = $propertyNameType->getConstantStrings();
                if (count($strings) === 0) {
                    // handle subtractions of a dynamic property fetch
                    foreach (array_keys($properties) as $propertyName) {
                        if ((new ConstantStringType($propertyName))->isSuperTypeOf($propertyNameType)->no()) {
                            continue;
                        }
                        unset($properties[$propertyName]);
                    }
                    continue;
                }
                $propertyNames = array_map(static fn(ConstantStringType $type): string => $type->getValue(), $strings);
            }
            if ($fetch instanceof Node\Expr\PropertyFetch) {
                $fetchedOnType = $usageScope->getType($fetch->var);
            } else if ($fetch->class instanceof Node\Name) {
                $fetchedOnType = $usageScope->resolveTypeByName($fetch->class);
            } else {
                $fetchedOnType = $usageScope->getType($fetch->class)->getObjectTypeOrClassStringObjectType();
            }
            foreach ($propertyNames as $propertyName) {
                if (!array_key_exists($propertyName, $properties)) {
                    continue;
                }
                $propertyNode = $properties[$propertyName]['node'];
                if ((bool) ($propertyNode->flags & \PhpParser\Modifiers::STATIC)) {
                    $propertyReflection = $usageScope->getStaticPropertyReflection($fetchedOnType, $propertyName);
                } else {
                    $propertyReflection = $usageScope->getInstancePropertyReflection($fetchedOnType, $propertyName);
                }
                if ($propertyReflection === null) {
                    if (!$classType->isSuperTypeOf($fetchedOnType)->no()) {
                        if ($usage instanceof PropertyRead) {
                            $properties[$propertyName]['read'] = \true;
                            $properties[$propertyName]['hasTrueRead'] = \true;
                        } else {
                            $properties[$propertyName]['written'] = \true;
                        }
                    }
                    continue;
                }
                if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
                    if (!$classType->isSuperTypeOf($fetchedOnType)->no()) {
                        if ($usage instanceof PropertyRead) {
                            $properties[$propertyName]['read'] = \true;
                            $properties[$propertyName]['hasTrueRead'] = \true;
                        } else {
                            $properties[$propertyName]['written'] = \true;
                        }
                    }
                    continue;
                }
                if ($usage instanceof PropertyRead) {
                    $properties[$propertyName]['read'] = \true;
                    if (!$this->isPropertySelfWrite($usageScope, $propertyName, $propertyNode, $classReflection->getName())) {
                        $properties[$propertyName]['hasTrueRead'] = \true;
                    }
                } else {
                    $properties[$propertyName]['written'] = \true;
                }
            }
        }
        foreach ($properties as $propertyName => $data) {
            if (!$data['read'] || $data['hasTrueRead']) {
                continue;
            }
            if (!$data['node']->isPromoted) {
                continue;
            }
            $properties[$propertyName]['read'] = \false;
        }
        [$uninitializedProperties] = $node->getUninitializedProperties($scope, []);
        $errors = [];
        foreach ($properties as $name => $data) {
            $propertyNode = $data['node'];
            if ((bool) ($propertyNode->flags & \PhpParser\Modifiers::STATIC)) {
                $propertyName = sprintf('Static property %s::$%s', $classReflection->getDisplayName(), $name);
            } else {
                $propertyName = sprintf('Property %s::$%s', $classReflection->getDisplayName(), $name);
            }
            $tip = sprintf('See: %s', 'https://phpstan.org/developing-extensions/always-read-written-properties');
            if (!$data['read']) {
                if (!$data['written']) {
                    $errors[] = RuleErrorBuilder::message(sprintf('%s is unused.', $propertyName))->line($propertyNode->getStartLine())->tip($tip)->identifier('property.unused')->build();
                } else if ($data['onlyReadable']) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Readable %s is never read.', lcfirst($propertyName)))->line($propertyNode->getStartLine())->identifier('property.neverRead')->build();
                } else {
                    $errors[] = RuleErrorBuilder::message(sprintf('%s is never read, only written.', $propertyName))->line($propertyNode->getStartLine())->identifier('property.onlyWritten')->tip($tip)->build();
                }
            } elseif (!$data['written'] && (!array_key_exists($name, $uninitializedProperties) || !$this->checkUninitializedProperties)) {
                if ($data['onlyWritable']) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Writable %s is never written.', lcfirst($propertyName)))->line($propertyNode->getStartLine())->identifier('property.neverWritten')->build();
                } else {
                    $errors[] = RuleErrorBuilder::message(sprintf('%s is never written, only read.', $propertyName))->line($propertyNode->getStartLine())->identifier('property.onlyRead')->tip($tip)->build();
                }
            }
        }
        return $errors;
    }
    /**
     * A private property redeclared from a used trait is the very property the trait's
     * own methods read and write. Callers must only rely on this when the trait's body was
     * not traversed, otherwise those usages are visible and no guessing is needed.
     */
    private function isRedeclaringPrivateTraitProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        foreach ($classReflection->getTraits() as $trait) {
            if (!$trait->hasNativeProperty($propertyName)) {
                continue;
            }
            if (!$trait->getNativeProperty($propertyName)->isPrivate()) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    private function isPropertySelfWrite(Scope $usageScope, string $propertyName, ClassPropertyNode $propertyNode, string $className): bool
    {
        if (!$propertyNode->isPromoted) {
            return \false;
        }
        $callStack = $usageScope->getFunctionCallStackWithParameters();
        if ($callStack === []) {
            return \false;
        }
        $lastCall = $callStack[count($callStack) - 1];
        [$calleeReflection, $parameterReflection] = $lastCall;
        if (!$calleeReflection instanceof MethodReflection) {
            return \false;
        }
        if ($calleeReflection->getName() !== '__construct') {
            return \false;
        }
        if ($calleeReflection->getDeclaringClass()->getName() !== $className) {
            return \false;
        }
        if ($parameterReflection === null) {
            return \false;
        }
        return $parameterReflection->getName() === $propertyName;
    }
}
