<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php85\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\NodeAnalyzer\ClassAnalyzer;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/override_properties
 *
 * @see \Flames\Code\Upgrade\Rules\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRectorTest
 */
final class AddOverrideAttributeToOverriddenPropertiesRector extends AbstractRector implements MinPhpVersionInterface
{
    private const string OVERRIDE_CLASS = 'Override';
    private bool $hasChanged = \false;
    public function __construct(private readonly ReflectionProvider $reflectionProvider, private readonly ClassAnalyzer $classAnalyzer, private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add override attribute to overridden properties', [new CodeSample(<<<'CODE_SAMPLE'
class ParentClass
{
    public string $name;
}

final class ChildClass extends ParentClass
{
    public string $name;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class ParentClass
{
    public string $name;
}

final class ChildClass extends ParentClass
{
    #[\Override]
    public string $name;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::OVERRIDE_ATTRIBUTE_ON_PROPERTIES;
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->classAnalyzer->isAnonymousClass($node)) {
            return null;
        }
        $className = (string) $this->getName($node);
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($className);
        $parentClassReflections = $classReflection->getParents();
        if ($parentClassReflections === []) {
            return null;
        }
        $this->hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            $this->processProperty($property, $parentClassReflections);
        }
        if ($this->hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param ClassReflection[] $parentClassReflections
     */
    private function processProperty(Property $property, array $parentClassReflections): void
    {
        if ($this->shouldSkipProperty($property)) {
            return;
        }
        foreach ($property->props as $propertyProperty) {
            $propertyName = $this->getName($propertyProperty);
            if ($propertyName === null) {
                continue;
            }
            if ($this->isPropertyOverridden($propertyName, $parentClassReflections)) {
                $property->attrGroups[] = new AttributeGroup([new Attribute(new FullyQualified(self::OVERRIDE_CLASS))]);
                $this->hasChanged = \true;
                return;
            }
        }
    }
    private function shouldSkipProperty(Property $property): bool
    {
        if ($property->isPrivate()) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttribute($property, self::OVERRIDE_CLASS);
    }
    /**
     * @param ClassReflection[] $parentClassReflections
     */
    private function isPropertyOverridden(string $propertyName, array $parentClassReflections): bool
    {
        foreach ($parentClassReflections as $parentClassReflection) {
            if (!$parentClassReflection->hasNativeProperty($propertyName)) {
                continue;
            }
            $parentProperty = $parentClassReflection->getNativeProperty($propertyName);
            return !$parentProperty->isPrivate();
        }
        return \false;
    }
}
