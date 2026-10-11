<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\AnnotationsToAttributes\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitAttribute;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRectorTest
 */
final class DataProviderAnnotationToAttributeRector extends AbstractRector implements MinPhpVersionInterface, ComposerPackageConstraintInterface
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change dataProvider annotations to attribute', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @dataProvider someMethod()
     */
    public function test(): void
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('someMethod')]
    public function test(): void
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
        return [ClassMethod::class];
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=10.0');
    }
    public function provideMinPhpVersion(): int
    {
        /**
         * This rule just work for phpunit 10,
         * And as php 8.1 is the min version supported by phpunit 10, then we decided to let this version as minimum.
         *
         * You can see more detail in this issue: https://github.com/rectorphp/rector-phpunit/issues/272
         */
        return PhpVersion::PHP_81;
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if (!$this->reflectionProvider->hasClass(PHPUnitAttribute::DATA_PROVIDER)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        /** @var PhpDocTagNode[] $desiredTagValueNodes */
        $desiredTagValueNodes = $phpDocInfo->getTagsByName('dataProvider');
        if ($desiredTagValueNodes === []) {
            return null;
        }
        foreach ($desiredTagValueNodes as $desiredTagValueNode) {
            if (!$desiredTagValueNode->value instanceof GenericTagValueNode && !$desiredTagValueNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            if ($desiredTagValueNode->value instanceof GenericTagValueNode) {
                $originalAttributeValue = $desiredTagValueNode->value->value;
            } else {
                $originalAttributeValue = $desiredTagValueNode->value->getOriginalContent();
            }
            $originalAttributeValueToken = strtok($originalAttributeValue ?: '', " \t\n\r\x00\v");
            if ($originalAttributeValueToken === \false) {
                continue;
            }
            $attributeGroup = $this->createAttributeGroup($node, $originalAttributeValueToken);
            if ($attributeGroup instanceof AttributeGroup) {
                $node->attrGroups[] = $attributeGroup;
            }
            // cleanup
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $desiredTagValueNode);
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    private function createAttributeGroup(ClassMethod $classMethod, string $originalAttributeValue): ?AttributeGroup
    {
        $methodName = trim($originalAttributeValue, '()');
        $className = '';
        if (str_contains($methodName, '::')) {
            [$className, $methodName] = explode('::', $methodName, 2);
        }
        if ($className !== '') {
            if ($className[0] !== '\\') {
                $className = '\\' . $className;
            }
            $attributeGroup = $this->phpAttributeGroupFactory->createFromClassWithItems('PHPUnit\Framework\Attributes\DataProviderExternal', [$className . '::class', $methodName]);
        } else {
            $attributeGroup = $this->phpAttributeGroupFactory->createFromClassWithItems(PHPUnitAttribute::DATA_PROVIDER, [$methodName]);
        }
        foreach ($classMethod->attrGroups as $existingAttributeGroup) {
            if ($this->nodeComparator->areNodesEqual($existingAttributeGroup, $attributeGroup)) {
                return null;
            }
        }
        return $attributeGroup;
    }
}
