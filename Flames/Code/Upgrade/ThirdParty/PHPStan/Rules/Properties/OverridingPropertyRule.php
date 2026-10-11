<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(0)]
final class OverridingPropertyRule implements Rule
{
    private PhpVersion $phpVersion;
    private bool $checkPhpDocMethodSignatures;
    private bool $reportMaybes;
    private ?bool $checkMissingOverridePropertyAttribute;
    private bool $checkMissingOverrideMethodAttribute;
    public function __construct(
        PhpVersion $phpVersion,
        #[AutowiredParameter]
        bool $checkPhpDocMethodSignatures,
        #[AutowiredParameter('%reportMaybesInPropertyPhpDocTypes%')]
        bool $reportMaybes,
        #[AutowiredParameter]
        ?bool $checkMissingOverridePropertyAttribute,
        #[AutowiredParameter]
        bool $checkMissingOverrideMethodAttribute
    )
    {
        $this->phpVersion = $phpVersion;
        $this->checkPhpDocMethodSignatures = $checkPhpDocMethodSignatures;
        $this->reportMaybes = $reportMaybes;
        $this->checkMissingOverridePropertyAttribute = $checkMissingOverridePropertyAttribute;
        $this->checkMissingOverrideMethodAttribute = $checkMissingOverrideMethodAttribute;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->classReflection;
        $prototype = $this->findPrototype($classReflection, $node->name);
        if ($prototype === null) {
            if ($this->hasOverrideAttribute($node->getAttrGroups())) {
                $originalNode = $node->originalNode;
                if ($originalNode instanceof Node\Stmt\Property) {
                    /** @var Node\Stmt\Property $originalNode */
                    $originalNode = $originalNode->getAttribute('originalPropertyStmt');
                }
                return [RuleErrorBuilder::message(sprintf('Property %s::$%s has #[\Override] attribute but does not override any property.', $node->classReflection->getDisplayName(), $node->name))->nonIgnorable()->identifier('property.override')->fixNode($originalNode, function ($property) {
                    $property->attrGroups = $this->filterOverrideAttribute($property->attrGroups);
                    return $property;
                })->build()];
            }
            return [];
        }
        $errors = [];
        if (($this->checkMissingOverridePropertyAttribute === \true || $this->checkMissingOverridePropertyAttribute === null && $this->checkMissingOverrideMethodAttribute && $this->phpVersion->versionId >= 80500) && !$scope->isInTrait() && !$this->hasOverrideAttribute($node->getAttrGroups())) {
            $originalNode = $node->originalNode;
            if ($originalNode instanceof Node\Stmt\Property) {
                /** @var Node\Stmt\Property $originalNode */
                $originalNode = $originalNode->getAttribute('originalPropertyStmt');
            }
            $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overrides property %s::$%s but is missing the #[\Override] attribute.', $node->classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $prototype->getName()))->identifier('property.missingOverride')->fixNode($originalNode, static function ($property) {
                $property->attrGroups[] = new Node\AttributeGroup([new Attribute(new Node\Name\FullyQualified('Override'))]);
                return $property;
            })->build();
        }
        if ($prototype->isStatic()) {
            if (!(bool) ($node->flags & \PhpParser\Modifiers::STATIC)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Non-static property %s::$%s overrides static property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.nonStatic')->nonIgnorable()->build();
            }
        } elseif ((bool) ($node->flags & \PhpParser\Modifiers::STATIC)) {
            $errors[] = RuleErrorBuilder::message(sprintf('Static property %s::$%s overrides non-static property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.static')->nonIgnorable()->build();
        }
        if ($prototype->isReadOnly()) {
            if (!((bool) ($node->flags & \PhpParser\Modifiers::READONLY) || $node->isReadonlyClass)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Readwrite property %s::$%s overrides readonly property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.readWrite')->nonIgnorable()->build();
            }
        } elseif ((bool) ($node->flags & \PhpParser\Modifiers::READONLY) || $node->isReadonlyClass) {
            if (!($this->phpVersion->versionId >= 80400) || $prototype->isWritable()) {
                $errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s overrides readwrite property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.readOnly')->nonIgnorable()->build();
            }
        }
        $propertyReflection = $classReflection->getNativeProperty($node->name);
        if ($this->phpVersion->versionId >= 80400) {
            if ($prototype->isReadable()) {
                if (!$propertyReflection->isReadable()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overriding readable property %s::$%s also has to be readable.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.notReadable')->nonIgnorable()->build();
                }
            }
            if ($prototype->isWritable()) {
                if (!$propertyReflection->isWritable()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overriding writable property %s::$%s also has to be writable.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.notWritable')->nonIgnorable()->build();
                }
            }
            if ((bool) ($node->flags & \PhpParser\Modifiers::ABSTRACT) && $prototype->isAbstract()->no()) {
                foreach (['get', 'set'] as $hookType) {
                    if (!$propertyReflection->hasHook($hookType) || !$prototype->hasHook($hookType)) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message(sprintf('Cannot make non-abstract method %s::$%s::%s() abstract in class %s.', $prototype->getDeclaringClass()->getDisplayName(\true), $node->name, $hookType, $classReflection->getDisplayName()))->nonIgnorable()->identifier('property.abstractOverridingNonAbstractHook')->build();
                }
            }
        }
        if ($prototype->isPublic()) {
            if (!(($node->flags & \PhpParser\Modifiers::PUBLIC) !== 0 || ($node->flags & \PhpParser\Modifiers::VISIBILITY_MASK) === 0)) {
                $errors[] = RuleErrorBuilder::message(sprintf('%s property %s::$%s overriding public property %s::$%s should also be public.', (bool) ($node->flags & \PhpParser\Modifiers::PRIVATE) ? 'Private' : 'Protected', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.visibility')->nonIgnorable()->build();
            }
        } elseif ((bool) ($node->flags & \PhpParser\Modifiers::PRIVATE)) {
            $errors[] = RuleErrorBuilder::message(sprintf('Private property %s::$%s overriding protected property %s::$%s should be protected or public.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.visibility')->nonIgnorable()->build();
        }
        if ($prototype->isFinalByKeyword()->yes()) {
            $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overrides final property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.parentPropertyFinal')->nonIgnorable()->build();
        } elseif ($prototype->isFinal()->yes()) {
            $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overrides @final property %s::$%s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.parentPropertyFinalByPhpDoc')->build();
        }
        $typeErrors = [];
        $nativeType = $node->type;
        if ($prototype->hasNativeType()) {
            if ($nativeType === null) {
                $typeErrors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s overriding property %s::$%s (%s) should also have native type %s.', $classReflection->getDisplayName(), $node->name, $prototype->getDeclaringClass()->getDisplayName(), $node->name, $prototype->getNativeType()->describe(VerbosityLevel::typeOnly()), $prototype->getNativeType()->describe(VerbosityLevel::typeOnly())))->identifier('property.missingNativeType')->nonIgnorable()->build();
            } else if (!$prototype->getNativeType()->equals($nativeType)) {
                if ($this->phpVersion->versionId >= 80400 && ($prototype->isVirtual()->yes() || $prototype->isAbstract()->yes()) && (!$prototype->isReadable() || !$prototype->isWritable())) {
                    if (!$prototype->isReadable()) {
                        if (!$nativeType->isSuperTypeOf($prototype->getNativeType())->yes()) {
                            $typeErrors[] = RuleErrorBuilder::message(sprintf('Type %s of property %s::$%s is not contravariant with type %s of overridden property %s::$%s.', $nativeType->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName(), $node->name, $prototype->getNativeType()->describe(VerbosityLevel::typeOnly()), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.nativeType')->nonIgnorable()->build();
                        }
                    } elseif (!$prototype->getNativeType()->isSuperTypeOf($nativeType)->yes()) {
                        $typeErrors[] = RuleErrorBuilder::message(sprintf('Type %s of property %s::$%s is not covariant with type %s of overridden property %s::$%s.', $nativeType->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName(), $node->name, $prototype->getNativeType()->describe(VerbosityLevel::typeOnly()), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.nativeType')->nonIgnorable()->build();
                    }
                } else {
                    $typeErrors[] = RuleErrorBuilder::message(sprintf('Type %s of property %s::$%s is not the same as type %s of overridden property %s::$%s.', $nativeType->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName(), $node->name, $prototype->getNativeType()->describe(VerbosityLevel::typeOnly()), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.nativeType')->nonIgnorable()->build();
                }
            }
        } elseif ($nativeType !== null) {
            $typeErrors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s (%s) overriding property %s::$%s should not have a native type.', $classReflection->getDisplayName(), $node->name, $nativeType->describe(VerbosityLevel::typeOnly()), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.extraNativeType')->nonIgnorable()->build();
        }
        $errors = array_merge($errors, $typeErrors);
        if (!$this->checkPhpDocMethodSignatures) {
            return $errors;
        }
        if (count($typeErrors) > 0) {
            return $errors;
        }
        if ($prototype->getReadableType()->equals($propertyReflection->getReadableType())) {
            return $errors;
        }
        $verbosity = VerbosityLevel::getRecommendedLevelByType($prototype->getReadableType(), $propertyReflection->getReadableType());
        if ($this->phpVersion->versionId >= 80400 && ($prototype->isVirtual()->yes() || $prototype->isAbstract()->yes()) && (!$prototype->isReadable() || !$prototype->isWritable())) {
            if (!$prototype->isReadable()) {
                if (!$propertyReflection->getReadableType()->isSuperTypeOf($prototype->getReadableType())->yes()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc type %s of property %s::$%s is not contravariant with PHPDoc type %s of overridden property %s::$%s.', $propertyReflection->getReadableType()->describe($verbosity), $classReflection->getDisplayName(), $node->name, $prototype->getReadableType()->describe($verbosity), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.phpDocType')->tip(sprintf("You can fix 3rd party PHPDoc types with stub files:\n   %s", '<fg=cyan>https://phpstan.org/user-guide/stub-files</>'))->build();
                }
            } elseif (!$prototype->getReadableType()->isSuperTypeOf($propertyReflection->getReadableType())->yes()) {
                $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc type %s of property %s::$%s is not covariant with PHPDoc type %s of overridden property %s::$%s.', $propertyReflection->getReadableType()->describe($verbosity), $classReflection->getDisplayName(), $node->name, $prototype->getReadableType()->describe($verbosity), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.phpDocType')->tip(sprintf("You can fix 3rd party PHPDoc types with stub files:\n   %s", '<fg=cyan>https://phpstan.org/user-guide/stub-files</>'))->build();
            }
            return $errors;
        }
        $isSuperType = $prototype->getReadableType()->isSuperTypeOf($propertyReflection->getReadableType());
        $canBeTurnedOffError = RuleErrorBuilder::message(sprintf('PHPDoc type %s of property %s::$%s is not the same as PHPDoc type %s of overridden property %s::$%s.', $propertyReflection->getReadableType()->describe($verbosity), $classReflection->getDisplayName(), $node->name, $prototype->getReadableType()->describe($verbosity), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.phpDocType')->tip(sprintf("You can fix 3rd party PHPDoc types with stub files:\n   %s\n   This error can be turned off by setting\n   %s", '<fg=cyan>https://phpstan.org/user-guide/stub-files</>', '<fg=cyan>reportMaybesInPropertyPhpDocTypes: false</> in your <fg=cyan>%configurationFile%</>.'))->build();
        $cannotBeTurnedOffError = RuleErrorBuilder::message(sprintf('PHPDoc type %s of property %s::$%s is %s PHPDoc type %s of overridden property %s::$%s.', $propertyReflection->getReadableType()->describe($verbosity), $classReflection->getDisplayName(), $node->name, $this->reportMaybes ? 'not the same as' : 'not covariant with', $prototype->getReadableType()->describe($verbosity), $prototype->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.phpDocType')->tip(sprintf("You can fix 3rd party PHPDoc types with stub files:\n   %s", '<fg=cyan>https://phpstan.org/user-guide/stub-files</>'))->build();
        if ($this->reportMaybes) {
            if (!$isSuperType->yes()) {
                $errors[] = $cannotBeTurnedOffError;
            } else {
                $errors[] = $canBeTurnedOffError;
            }
        } else if (!$isSuperType->yes()) {
            $errors[] = $cannotBeTurnedOffError;
        }
        return $errors;
    }
    private function findPrototype(ClassReflection $classReflection, string $propertyName): ?PhpPropertyReflection
    {
        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null) {
            return $this->findPrototypeInInterfaces($classReflection, $propertyName);
        }
        if (!$parentClass->hasNativeProperty($propertyName)) {
            return $this->findPrototypeInInterfaces($classReflection, $propertyName);
        }
        $property = $parentClass->getNativeProperty($propertyName);
        if ($property->isPrivate()) {
            return $this->findPrototypeInInterfaces($classReflection, $propertyName);
        }
        return $property;
    }
    private function findPrototypeInInterfaces(ClassReflection $classReflection, string $propertyName): ?PhpPropertyReflection
    {
        foreach ($classReflection->getInterfaces() as $interface) {
            if (!$interface->hasNativeProperty($propertyName)) {
                continue;
            }
            return $interface->getNativeProperty($propertyName);
        }
        return null;
    }
    /**
     * @param Node\AttributeGroup[] $attrGroups
     * @return Node\AttributeGroup[]
     */
    private function filterOverrideAttribute(array $attrGroups): array
    {
        foreach ($attrGroups as $i => $attrGroup) {
            foreach ($attrGroup->attrs as $j => $attr) {
                if (strtolower($attr->name->name) !== 'override') {
                    continue;
                }
                unset($attrGroup->attrs[$j]);
                if (count($attrGroup->attrs) !== 0) {
                    continue;
                }
                unset($attrGroups[$i]);
            }
        }
        return $attrGroups;
    }
    /**
     * @param Node\AttributeGroup[] $attrGroups
     */
    private function hasOverrideAttribute(array $attrGroups): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (strtolower($attr->name->name) === 'override') {
                    return \true;
                }
            }
        }
        return \false;
    }
}
