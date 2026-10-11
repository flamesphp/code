<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node as DocNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Exception\Configuration\InvalidConfigurationException;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\Rules\Php80\NodeFactory\AttrGroupsFactory;
use Flames\Code\Upgrade\Rules\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AttributeValueAndDocComment;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Util\StringUtils;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AnnotationToAttributeRectorTest
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AnnotationToAttributePhp81NestedAttributesRectorTest
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AnnotationToAttributeMultipleCallAnnotationToAttributeRectorTest
 */
final class AnnotationToAttributeRector extends AbstractRector implements ConfigurableRectorInterface, MinPhpVersionInterface
{
    /**
     * Any unicode letter, to tell a real doc comment apart from leftover annotation syntax
     */
    private const string LETTER_REGEX = '#\p{L}#u';
    /**
     * @var AnnotationToAttribute[]
     */
    private array $annotationsToAttributes = [];
    public function __construct(private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory, private readonly AttrGroupsFactory $attrGroupsFactory, private readonly PhpDocTagRemover $phpDocTagRemover, private readonly AttributeGroupNamedArgumentManipulator $attributeGroupNamedArgumentManipulator, private readonly UseImportsResolver $useImportsResolver, private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer, private readonly DocBlockUpdater $docBlockUpdater, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ReflectionProvider $reflectionProvider, private readonly \Flames\Code\Upgrade\Rules\Php80\Rector\Class_\AttributeValueResolver $attributeValueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change annotation to attribute', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

class SymfonyRoute
{
    /**
     * @Route("/path", name="action")
     */
    public function action()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Routing\Annotation\Route;

class SymfonyRoute
{
    #[Route(path: '/path', name: 'action')]
    public function action()
    {
    }
}
CODE_SAMPLE
, [new AnnotationToAttribute('Symfony\Component\Routing\Annotation\Route')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, Param::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class, Interface_::class];
    }
    /**
     * @param Class_|Property|Param|ClassMethod|Function_|Closure|ArrowFunction|Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->annotationsToAttributes === []) {
            throw new InvalidConfigurationException(sprintf('The "%s" rule requires configuration.', self::class));
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $uses = $this->useImportsResolver->resolveBareUses();
        // 1. Doctrine annotation classes
        $annotationAttributeGroups = $this->processDoctrineAnnotationClasses($phpDocInfo, $uses);
        // 2. bare tags without annotation class, e.g. "@require"
        $genericAttributeGroups = $this->processGenericTags($phpDocInfo);
        $attributeGroups = array_merge($annotationAttributeGroups, $genericAttributeGroups);
        if ($attributeGroups === []) {
            return null;
        }
        // 3. Reprint docblock
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $this->attributeGroupNamedArgumentManipulator->decorate($attributeGroups);
        $node->attrGroups = array_merge($node->attrGroups, $attributeGroups);
        return $node;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, AnnotationToAttribute::class);
        $this->annotationsToAttributes = $this->resolveWithChangedAttributesClass($configuration);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }
    /**
     * @param AnnotationToAttribute[] $configuration
     * @return AnnotationToAttribute[] $configuration
     */
    private function resolveWithChangedAttributesClass(array $configuration): array
    {
        foreach ($configuration as $config) {
            /** @var AnnotationToAttribute $config */
            if ($config->getAttributeClass() !== $config->getTag()) {
                // add to make sure apply after use statement changed
                $configuration[] = new AnnotationToAttribute($config->getAttributeClass(), $config->getAttributeClass(), $config->getClassReferenceFields(), $config->getUseValueAsAttributeArgument());
            }
        }
        return $configuration;
    }
    /**
     * @return AttributeGroup[]
     */
    private function processGenericTags(PhpDocInfo $phpDocInfo): array
    {
        $attributeGroups = [];
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->traverseWithCallable($phpDocInfo->getPhpDocNode(), '', function (DocNode $docNode) use (&$attributeGroups) {
            if (!$docNode instanceof PhpDocTagNode) {
                return null;
            }
            if (!$docNode->value instanceof GenericTagValueNode && !$docNode->value instanceof DoctrineAnnotationTagValueNode) {
                return null;
            }
            $tag = trim($docNode->name, '@');
            // not a basic one
            if (str_contains($tag, '\\')) {
                return null;
            }
            foreach ($this->annotationsToAttributes as $annotationToAttribute) {
                $desiredTag = $annotationToAttribute->getTag();
                if (strtolower($desiredTag) !== strtolower($tag)) {
                    continue;
                }
                // make sure the attribute class really exists to avoid error on early upgrade
                if (!$this->reflectionProvider->hasClass($annotationToAttribute->getAttributeClass())) {
                    continue;
                }
                $attributeValueAndDocComment = $this->attributeValueResolver->resolve($annotationToAttribute, $docNode);
                $attributeGroups[] = $this->phpAttributeGroupFactory->createFromSimpleTag($annotationToAttribute, $attributeValueAndDocComment instanceof AttributeValueAndDocComment ? $attributeValueAndDocComment->attributeValue : null);
                // keep partial original comment, if useful
                if ($attributeValueAndDocComment instanceof AttributeValueAndDocComment && $attributeValueAndDocComment->docComment) {
                    return new PhpDocTextNode($attributeValueAndDocComment->docComment);
                }
                return PhpDocNodeTraverser::NODE_REMOVE;
            }
            return null;
        });
        return $attributeGroups;
    }
    /**
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    private function processDoctrineAnnotationClasses(PhpDocInfo $phpDocInfo, array $uses): array
    {
        if ($phpDocInfo->getPhpDocNode()->children === []) {
            return [];
        }
        $doctrineTagAndAnnotationToAttributes = [];
        $doctrineTagValueNodes = [];
        foreach ($phpDocInfo->getPhpDocNode()->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if (!$phpDocChildNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            $doctrineTagValueNode = $phpDocChildNode->value;
            $annotationToAttribute = $this->matchAnnotationToAttribute($doctrineTagValueNode);
            if (!$annotationToAttribute instanceof AnnotationToAttribute) {
                continue;
            }
            if ($annotationToAttribute->getUseValueAsAttributeArgument()) {
                /* Will be processed by processGenericTags instead */
                continue;
            }
            if (!$this->isExistingAttributeClass($annotationToAttribute)) {
                continue;
            }
            $doctrineTagAndAnnotationToAttributes[] = new DoctrineTagAndAnnotationToAttribute($doctrineTagValueNode, $annotationToAttribute);
            $doctrineTagValueNodes[] = $doctrineTagValueNode;
        }
        $attributeGroups = $this->attrGroupsFactory->create($doctrineTagAndAnnotationToAttributes, $uses);
        if ($this->phpAttributeAnalyzer->hasRemoveArrayState($attributeGroups)) {
            return [];
        }
        $phpDocNode = $phpDocInfo->getPhpDocNode();
        foreach ($doctrineTagValueNodes as $doctrineTagValueNode) {
            // keep a doc comment that followed the annotation on the next line(s)
            $trailingComment = $this->resolveTrailingComment((string) $doctrineTagValueNode->getOriginalContent());
            if ($trailingComment === null) {
                $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineTagValueNode);
                continue;
            }
            foreach ($phpDocNode->children as $key => $phpDocChildNode) {
                if (!$phpDocChildNode instanceof PhpDocTagNode) {
                    continue;
                }
                if ($phpDocChildNode->value !== $doctrineTagValueNode) {
                    continue;
                }
                $phpDocNode->children[$key] = new PhpDocTextNode($trailingComment);
            }
        }
        return $attributeGroups;
    }
    /**
     * A doctrine annotation swallows the doc comment placed on the line(s) below it into its original content.
     * Split it back out, so it is not removed together with the converted annotation.
     */
    private function resolveTrailingComment(string $originalContent): ?string
    {
        if (!str_starts_with($originalContent, '(')) {
            return null;
        }
        $depth = 0;
        $inString = \false;
        $stringChar = '';
        $length = strlen($originalContent);
        for ($i = 0; $i < $length; ++$i) {
            $char = $originalContent[$i];
            if ($inString) {
                if ($char === '\\') {
                    // skip escaped char
                    ++$i;
                } elseif ($char === $stringChar) {
                    $inString = \false;
                }
                continue;
            }
            if ($char === '"' || $char === "'") {
                $inString = \true;
                $stringChar = $char;
                continue;
            }
            if ($char === '(') {
                ++$depth;
                continue;
            }
            if ($char === ')') {
                --$depth;
                if ($depth === 0) {
                    $trailingComment = trim((string) substr($originalContent, $i + 1));
                    // keep only a real doc comment, not leftover annotation syntax (e.g. a stray ")")
                    if (!StringUtils::isMatch($trailingComment, self::LETTER_REGEX)) {
                        return null;
                    }
                    return $trailingComment;
                }
            }
        }
        return null;
    }
    private function matchAnnotationToAttribute(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): ?\Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute
    {
        foreach ($this->annotationsToAttributes as $annotationToAttribute) {
            if (!$doctrineAnnotationTagValueNode->hasClassName($annotationToAttribute->getTag())) {
                continue;
            }
            return $annotationToAttribute;
        }
        return null;
    }
    private function isExistingAttributeClass(AnnotationToAttribute $annotationToAttribute): bool
    {
        // make sure the attribute class really exists to avoid error on early upgrade
        if (!$this->reflectionProvider->hasClass($annotationToAttribute->getAttributeClass())) {
            return \false;
        }
        // make sure the class is marked as attribute
        $classReflection = $this->reflectionProvider->getClass($annotationToAttribute->getAttributeClass());
        return $classReflection->isAttributeClass();
    }
}
