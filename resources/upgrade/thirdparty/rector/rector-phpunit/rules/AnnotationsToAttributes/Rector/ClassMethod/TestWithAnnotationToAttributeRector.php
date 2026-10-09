<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\AnnotationsToAttributes\Rector\ClassMethod;

use FlamesPrefix202610\Nette\Utils\Json;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
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
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://docs.phpunit.de/en/10.0/annotations.html#testwith
 * @changelog https://docs.phpunit.de/en/10.2/attributes.html#testwith
 *
 * @see \Flames\Code\Upgrade\AnnotationsToAttributes\Rector\ClassMethod\TestWithAnnotationToAttributeRectorTest
 */
final class TestWithAnnotationToAttributeRector extends AbstractRector implements MinPhpVersionInterface, ComposerPackageConstraintInterface
{
    private const string TEST_WITH_ATTRIBUTE = 'PHPUnit\Framework\Attributes\TestWith';
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change @testWith() annotation to #[TestWith] attribute', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeFixture extends TestCase
{
    /**
     * @testWith ["foo"]
     *           ["bar"]
     */
    public function test(): void
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class SomeFixture extends TestCase
{
    #[TestWith(['foo'])]
    #[TestWith(['bar'])]
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
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }
        // make sure the attribute class exists
        if (!$this->reflectionProvider->hasClass(self::TEST_WITH_ATTRIBUTE)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $testWithPhpDocTagNodes = array_merge($phpDocInfo->getTagsByName('testWith'), $phpDocInfo->getTagsByName('testwith'));
        if ($testWithPhpDocTagNodes === []) {
            return null;
        }
        $attributeGroups = [];
        foreach ($testWithPhpDocTagNodes as $testWithPhpDocTagNode) {
            /** @var PhpDocTagNode $testWithPhpDocTagNode */
            if (!$testWithPhpDocTagNode->value instanceof GenericTagValueNode) {
                // not supported yet
                continue;
            }
            // test from doc blocks
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $testWithPhpDocTagNode);
            $genericTagValueNode = $testWithPhpDocTagNode->value;
            $testWithItems = explode("\n", trim($genericTagValueNode->value));
            foreach ($testWithItems as $testWithItem) {
                $jsonArray = Json::decode(trim($testWithItem), \true);
                $attributeGroups[] = $this->phpAttributeGroupFactory->createFromClassWithItems(self::TEST_WITH_ATTRIBUTE, [$jsonArray]);
            }
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $node->attrGroups = array_merge($node->attrGroups, $attributeGroups);
        return $node;
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=10.0');
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
}
