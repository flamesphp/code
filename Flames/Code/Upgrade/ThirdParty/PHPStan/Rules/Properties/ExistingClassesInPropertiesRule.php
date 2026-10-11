<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function array_merge;
use function sprintf;
/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(0)]
#[ValidatesStubFiles]
final class ExistingClassesInPropertiesRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private ClassNameCheck $classCheck;
    private UnresolvableTypeHelper $unresolvableTypeHelper;
    private PhpVersion $phpVersion;
    private bool $checkClassCaseSensitivity;
    private bool $checkThisOnly;
    private bool $discoveringSymbolsTip;
    public function __construct(
        ReflectionProvider $reflectionProvider,
        ClassNameCheck $classCheck,
        UnresolvableTypeHelper $unresolvableTypeHelper,
        PhpVersion $phpVersion,
        #[AutowiredParameter]
        bool $checkClassCaseSensitivity,
        #[AutowiredParameter]
        bool $checkThisOnly,
        #[AutowiredParameter('%tips.discoveringSymbols%')]
        bool $discoveringSymbolsTip
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classCheck = $classCheck;
        $this->unresolvableTypeHelper = $unresolvableTypeHelper;
        $this->phpVersion = $phpVersion;
        $this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
        $this->checkThisOnly = $checkThisOnly;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $propertyReflection = $node->classReflection->getNativeProperty($node->name);
        if ($this->checkThisOnly) {
            $referencedClasses = $propertyReflection->getNativeType()->getReferencedClasses();
        } else {
            $referencedClasses = array_merge($propertyReflection->getNativeType()->getReferencedClasses(), $propertyReflection->getPhpDocType()->getReferencedClasses());
        }
        $errors = [];
        foreach ($referencedClasses as $referencedClass) {
            if ($this->reflectionProvider->hasClass($referencedClass)) {
                if ($this->reflectionProvider->getClass($referencedClass)->isTrait()) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s has invalid type %s.', $propertyReflection->getDeclaringClass()->getDisplayName(), $node->name, $referencedClass))->identifier('property.trait')->build();
                }
                continue;
            }
            $errorBuilder = RuleErrorBuilder::message(sprintf('Property %s::$%s has unknown class %s as its type.', $propertyReflection->getDeclaringClass()->getDisplayName(), $node->name, $referencedClass))->identifier('class.notFound');
            if ($this->discoveringSymbolsTip) {
                $errorBuilder->tip('Learn more at https://phpstan.org/user-guide/discovering-symbols');
            }
            $errors[] = $errorBuilder->build();
        }
        $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static fn(string $class): ClassNameNodePair => new ClassNameNodePair($class, $node), $referencedClasses), ClassNameUsageLocation::from(ClassNameUsageLocation::PROPERTY_TYPE, ['property' => $propertyReflection]), $this->checkClassCaseSensitivity));
        $unresolvableType = $this->phpVersion->versionId >= 80100 ? $this->unresolvableTypeHelper->getUnresolvableType($propertyReflection->getNativeType()) : null;
        if ($unresolvableType !== null) {
            $errorBuilder = RuleErrorBuilder::message(sprintf('Property %s::$%s has unresolvable native type.', $propertyReflection->getDeclaringClass()->getDisplayName(), $node->name))->identifier('property.unresolvableNativeType');
            foreach ($unresolvableType->reasons as $reason) {
                $errorBuilder->addTip($reason);
            }
            $errors[] = $errorBuilder->build();
        }
        return $errors;
    }
}
