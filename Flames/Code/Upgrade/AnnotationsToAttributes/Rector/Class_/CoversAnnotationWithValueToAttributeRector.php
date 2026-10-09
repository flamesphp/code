<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\AnnotationsToAttributes\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\AnnotationsToAttributes\Rector\Class_\CoversAnnotationWithValueToAttributeRectorTest
 */
final class CoversAnnotationWithValueToAttributeRector extends AbstractRector implements MinPhpVersionInterface, ComposerPackageConstraintInterface
{
    private const string COVERS_FUNCTION_ATTRIBUTE = 'PHPUnit\Framework\Attributes\CoversFunction';
    private const string COVERTS_CLASS_ATTRIBUTE = 'PHPUnit\Framework\Attributes\CoversClass';
    private const string COVERTS_TRAIT_ATTRIBUTE = 'PHPUnit\Framework\Attributes\CoversTrait';
    private const string COVERS_METHOD_ATTRIBUTE = 'PHPUnit\Framework\Attributes\CoversMethod';
    public function __construct(private readonly PhpDocTagRemover $phpDocTagRemover, private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change covers annotations with value to attribute', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

/**
 * @covers SomeClass
 */
final class SomeTest extends TestCase
{
    /**
     * @covers ::someFunction()
     */
    public function test()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

#[CoversClass(SomeClass::class)]
#[CoversFunction('someFunction')]
final class SomeTest extends TestCase
{
    public function test()
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, ClassMethod::class];
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=10.0');
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    /**
     * @param Class_|ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        // avoid partial apply that may cause error
        if (!$this->reflectionProvider->hasClass(self::COVERS_FUNCTION_ATTRIBUTE) || !$this->reflectionProvider->hasClass(self::COVERTS_CLASS_ATTRIBUTE) || !$this->reflectionProvider->hasClass(self::COVERTS_TRAIT_ATTRIBUTE) || !$this->reflectionProvider->hasClass(self::COVERS_METHOD_ATTRIBUTE)) {
            return null;
        }
        if ($node instanceof Class_) {
            $coversAttributeGroups = $this->resolveClassAttributes($node);
            if ($coversAttributeGroups === []) {
                return null;
            }
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
            $node->attrGroups = array_merge($node->attrGroups, $coversAttributeGroups);
            return $node;
        }
        $hasChanged = $this->removeMethodCoversAnnotations($node);
        if ($hasChanged === \false) {
            return null;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    private function createAttributeGroup(string $annotationValue): ?AttributeGroup
    {
        if (str_starts_with($annotationValue, '::')) {
            $attributeClass = self::COVERS_FUNCTION_ATTRIBUTE;
            $attributeValue = [trim($annotationValue, ':()')];
        } elseif (str_contains($annotationValue, '::')) {
            $attributeClass = self::COVERS_METHOD_ATTRIBUTE;
            $attributeValue = [$this->getClass($annotationValue) . '::class', $this->getMethod($annotationValue)];
        } else {
            $attributeClass = self::COVERTS_CLASS_ATTRIBUTE;
            if ($this->reflectionProvider->hasClass($annotationValue)) {
                $classReflection = $this->reflectionProvider->getClass($annotationValue);
                if ($classReflection->isTrait()) {
                    $attributeClass = self::COVERTS_TRAIT_ATTRIBUTE;
                    if (!$this->reflectionProvider->hasClass($attributeClass)) {
                        return null;
                    }
                }
            }
            $attributeValue = [trim($annotationValue) . '::class'];
        }
        return $this->phpAttributeGroupFactory->createFromClassWithItems($attributeClass, $attributeValue);
    }
    /**
     * @return array<string, AttributeGroup>
     */
    private function resolveClassAttributes(Class_ $class): array
    {
        $coversDefaultGroups = [];
        $coversGroups = [];
        $methodGroups = [];
        $hasCoversDefault = \false;
        $coversDefaultClass = '';
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($class);
        if ($phpDocInfo instanceof PhpDocInfo) {
            $coversDefaultGroups = $this->handleCoversDefaultClass($phpDocInfo);
            // If there is a ::coversDefaultClass, @covers ::function will refer to class methods, otherwise it will refer to global functions.
            $hasCoversDefault = $coversDefaultGroups !== [];
            $coversDefaultClass = $hasCoversDefault ? $this->getName($coversDefaultGroups[0]->attrs[0]->args[0]->value) : null;
            $coversGroups = $this->handleCovers($phpDocInfo, $hasCoversDefault);
        }
        foreach ($class->getMethods() as $classMethod) {
            $methodGroups = array_merge($methodGroups, $this->resolveMethodAttributes($classMethod, $coversDefaultClass));
        }
        return array_merge($coversDefaultGroups, $coversGroups, $methodGroups);
    }
    /**
     * @return AttributeGroup[]
     */
    private function handleCoversDefaultClass(PhpDocInfo $phpDocInfo): array
    {
        $attributeGroups = [];
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('coversDefaultClass');
        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (!$desiredTagValueNode->value instanceof GenericTagValueNode) {
                continue;
            }
            $attributeGroup = $this->createAttributeGroup($desiredTagValueNode->value->value);
            // phpunit 10 may not fully support attribute
            if (!$attributeGroup instanceof AttributeGroup) {
                continue;
            }
            $attributeGroups[] = $attributeGroup;
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
        }
        return $attributeGroups;
    }
    /**
     * @return array<string, AttributeGroup>
     */
    private function handleCovers(PhpDocInfo $phpDocInfo, bool $hasCoversDefault): array
    {
        $attributeGroups = [];
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('covers');
        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (!$desiredTagValueNode->value instanceof GenericTagValueNode) {
                continue;
            }
            $covers = $desiredTagValueNode->value->value;
            if (str_starts_with($covers, '\\') || !$hasCoversDefault && str_starts_with($covers, '::')) {
                $attributeGroup = $this->createAttributeGroup($covers);
                // phpunit 10 may not fully support attribute
                if (!$attributeGroup instanceof AttributeGroup) {
                    continue;
                }
                $attributeGroups[$covers] = $attributeGroup;
                $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
            } elseif ($hasCoversDefault && str_starts_with($covers, '::')) {
                $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
            }
        }
        return $attributeGroups;
    }
    /**
     * @return array<string, AttributeGroup>
     */
    private function resolveMethodAttributes(ClassMethod $classMethod, ?string $coversDefaultClass): array
    {
        $hasCoversDefault = $coversDefaultClass !== null;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return [];
        }
        $attributeGroups = [];
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('covers');
        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (!$desiredTagValueNode->value instanceof GenericTagValueNode) {
                continue;
            }
            $covers = $desiredTagValueNode->value->value;
            if (!str_starts_with($covers, '\\') && !str_starts_with($covers, '::')) {
                continue;
            }
            if ($hasCoversDefault && str_starts_with($covers, '::')) {
                $covers = $coversDefaultClass . $covers;
            }
            $attributeGroup = $this->createAttributeGroup($covers);
            if (!$attributeGroup instanceof AttributeGroup) {
                continue;
            }
            $attributeGroups[$covers] = $attributeGroup;
        }
        return $attributeGroups;
    }
    private function removeMethodCoversAnnotations(ClassMethod $classMethod): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($classMethod);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return \false;
        }
        $hasChanged = \false;
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('covers');
        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (!$desiredTagValueNode->value instanceof GenericTagValueNode) {
                continue;
            }
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
            $hasChanged = \true;
        }
        return $hasChanged;
    }
    private function getClass(string $classWithMethod): string
    {
        return Strings::replace($classWithMethod, '/::.*$/');
    }
    private function getMethod(string $classWithMethod): string
    {
        return Strings::replace($classWithMethod, '/^.*::/');
    }
}
