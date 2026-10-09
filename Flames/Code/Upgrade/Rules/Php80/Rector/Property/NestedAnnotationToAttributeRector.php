<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php80\NodeFactory\NestedAttrGroupsFactory;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationPropertyToAttributeClass;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedAnnotationToAttribute;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedDoctrineTagAndAnnotationToAttribute;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Property\NestedAnnotationToAttributeRectorTest
 */
final class NestedAnnotationToAttributeRector extends AbstractRector implements ConfigurableRectorInterface, MinPhpVersionInterface
{
    /**
     * @var NestedAnnotationToAttribute[]
     */
    private array $nestedAnnotationsToAttributes = [];
    public function __construct(private readonly UseImportsResolver $useImportsResolver, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly NestedAttrGroupsFactory $nestedAttrGroupsFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change nested annotations to attributes', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

class SomeEntity
{
    /**
     * @ORM\JoinTable(name="join_table_name",
     *     joinColumns={@ORM\JoinColumn(name="origin_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="target_id")}
     * )
     */
    private $collection;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\ORM\Mapping as ORM;

class SomeEntity
{
    #[ORM\JoinTable(name: 'join_table_name')]
    #[ORM\JoinColumn(name: 'origin_id')]
    #[ORM\InverseJoinColumn(name: 'target_id')]
    private $collection;
}
CODE_SAMPLE
, [new NestedAnnotationToAttribute('Doctrine\ORM\Mapping\JoinTable', [new AnnotationPropertyToAttributeClass('Doctrine\ORM\Mapping\JoinColumn', 'joinColumns'), new AnnotationPropertyToAttributeClass('Doctrine\ORM\Mapping\InverseJoinColumn', 'inverseJoinColumns')])])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Property::class, Class_::class, Param::class];
    }
    /**
     * @param Property|Class_|Param $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $uses = $this->useImportsResolver->resolveBareUses();
        $attributeGroups = $this->transformDoctrineAnnotationClassesToAttributeGroups($phpDocInfo, $uses);
        if ($attributeGroups === []) {
            return null;
        }
        // 3. Reprint docblock
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $node->attrGroups = array_merge($node->attrGroups, $attributeGroups);
        $this->completeExtraUseImports($attributeGroups);
        return $node;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, NestedAnnotationToAttribute::class);
        $this->nestedAnnotationsToAttributes = $configuration;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_80;
    }
    /**
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    private function transformDoctrineAnnotationClassesToAttributeGroups(PhpDocInfo $phpDocInfo, array $uses): array
    {
        if ($phpDocInfo->getPhpDocNode()->children === []) {
            return [];
        }
        $nestedDoctrineTagAndAnnotationToAttributes = [];
        foreach ($phpDocInfo->getPhpDocNode()->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if (!$phpDocChildNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            $doctrineTagValueNode = $phpDocChildNode->value;
            $nestedAnnotationToAttribute = $this->matchAnnotationToAttribute($doctrineTagValueNode);
            if (!$nestedAnnotationToAttribute instanceof NestedAnnotationToAttribute) {
                continue;
            }
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineTagValueNode);
            $nestedDoctrineTagAndAnnotationToAttributes[] = new NestedDoctrineTagAndAnnotationToAttribute($doctrineTagValueNode, $nestedAnnotationToAttribute);
        }
        return $this->nestedAttrGroupsFactory->create($nestedDoctrineTagAndAnnotationToAttributes, $uses);
    }
    private function matchAnnotationToAttribute(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): ?\Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedAnnotationToAttribute
    {
        $doctrineResolvedClass = $doctrineAnnotationTagValueNode->identifierTypeNode->getAttribute(PhpDocAttributeKey::RESOLVED_CLASS);
        foreach ($this->nestedAnnotationsToAttributes as $nestedAnnotationToAttribute) {
            foreach ($nestedAnnotationToAttribute->getAnnotationPropertiesToAttributeClasses() as $annotationClass) {
                if ($annotationClass->getAttributeClass() === $doctrineResolvedClass) {
                    return $nestedAnnotationToAttribute;
                }
            }
            if ($doctrineResolvedClass !== $nestedAnnotationToAttribute->getTag()) {
                continue;
            }
            return $nestedAnnotationToAttribute;
        }
        return null;
    }
    /**
     * @param AttributeGroup[] $attributeGroups
     */
    private function completeExtraUseImports(array $attributeGroups): void
    {
        $fileNode = $this->file->getFileNode();
        if (!$fileNode instanceof FileNode) {
            return;
        }
        $pendingImports = $fileNode->getPendingImports();
        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                $namespacedAttrName = $attr->name->getAttribute(AttributeKey::EXTRA_USE_IMPORT);
                if (!is_string($namespacedAttrName)) {
                    continue;
                }
                $pendingImports->addUseImport(new FullyQualifiedObjectType($namespacedAttrName));
            }
        }
    }
}
