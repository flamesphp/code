<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\NodeDecorator\DoctrineAnnotationDecorator;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\TokenIteratorFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationPropertyToAttributeClass;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedAnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AttributeArrayNameInliner;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class PhpNestedAttributeGroupFactory
{
    public function __construct(private AnnotationToAttributeMapper $annotationToAttributeMapper, private \Flames\Code\Upgrade\PhpAttribute\NodeFactory\AttributeNameFactory $attributeNameFactory, private \Flames\Code\Upgrade\PhpAttribute\NodeFactory\NamedArgsFactory $namedArgsFactory, private AttributeArrayNameInliner $attributeArrayNameInliner, private TokenIteratorFactory $tokenIteratorFactory, private StaticDoctrineAnnotationParser $staticDoctrineAnnotationParser)
    {
    }
    /**
     * @param Use_[] $uses
     */
    public function create(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, NestedAnnotationToAttribute $nestedAnnotationToAttribute, array $uses): AttributeGroup
    {
        $values = $doctrineAnnotationTagValueNode->getValues();
        $values = $this->removeItems($values, $nestedAnnotationToAttribute);
        $args = $this->createArgsFromItems($values);
        $args = $this->attributeArrayNameInliner->inlineArrayToArgs($args);
        $attributeName = $this->attributeNameFactory->create($nestedAnnotationToAttribute, $doctrineAnnotationTagValueNode, $uses);
        $attribute = new Attribute($attributeName, $args);
        return new AttributeGroup([$attribute]);
    }
    /**
     * @return AttributeGroup[]
     */
    public function createNested(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, NestedAnnotationToAttribute $nestedAnnotationToAttribute): array
    {
        $attributeGroups = [];
        if ($nestedAnnotationToAttribute->hasExplicitParameters()) {
            return $this->createFromExplicitProperties($nestedAnnotationToAttribute, $doctrineAnnotationTagValueNode);
        }
        $nestedAnnotationPropertyToAttributeClass = $nestedAnnotationToAttribute->getAnnotationPropertiesToAttributeClasses()[0];
        foreach ($doctrineAnnotationTagValueNode->values as $arrayItemNode) {
            $nestedDoctrineAnnotationTagValueNode = $arrayItemNode->value;
            if (!$nestedDoctrineAnnotationTagValueNode instanceof CurlyListNode) {
                continue;
            }
            foreach ($nestedDoctrineAnnotationTagValueNode->values as $nestedArrayItemNode) {
                if (!$nestedArrayItemNode->value instanceof DoctrineAnnotationTagValueNode) {
                    continue;
                }
                $attributeArgs = $this->createAttributeArgs($nestedArrayItemNode->value);
                $originalIdentifier = $doctrineAnnotationTagValueNode->identifierTypeNode->name;
                $attributeName = $this->resolveAliasedAttributeName($originalIdentifier, $nestedAnnotationPropertyToAttributeClass);
                $attribute = new Attribute($attributeName, $attributeArgs);
                $attributeGroups[] = new AttributeGroup([$attribute]);
            }
        }
        return $attributeGroups;
    }
    /**
     * @return list<Arg>
     */
    private function createAttributeArgs(DoctrineAnnotationTagValueNode $nestedDoctrineAnnotationTagValueNode): array
    {
        $args = $this->createArgsFromItems($nestedDoctrineAnnotationTagValueNode->getValues());
        return $this->attributeArrayNameInliner->inlineArrayToArgs($args);
    }
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     * @return list<Arg>
     */
    private function createArgsFromItems(array $arrayItemNodes): array
    {
        $arrayItemNodes = $this->annotationToAttributeMapper->map($arrayItemNodes);
        $values = $arrayItemNodes instanceof Array_ ? $arrayItemNodes->items : $arrayItemNodes;
        return $this->namedArgsFactory->createFromValues($values);
    }
    /**
     * @todo improve this hardcoded approach later
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name
     */
    private function resolveAliasedAttributeName(string $originalIdentifier, AnnotationPropertyToAttributeClass $annotationPropertyToAttributeClass)
    {
        /** @var string $shortDoctrineAttributeName */
        $shortDoctrineAttributeName = Strings::after($annotationPropertyToAttributeClass->getAttributeClass(), '\\', -1);
        if (str_starts_with($originalIdentifier, '@ORM')) {
            // or alias
            return new Name('ORM\\' . $shortDoctrineAttributeName);
        }
        // short alias
        if (!str_contains($originalIdentifier, '\\')) {
            return new Name($shortDoctrineAttributeName);
        }
        return new FullyQualified($annotationPropertyToAttributeClass->getAttributeClass());
    }
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     * @return ArrayItemNode[]
     */
    private function removeItems(array $arrayItemNodes, NestedAnnotationToAttribute $nestedAnnotationToAttribute): array
    {
        foreach ($nestedAnnotationToAttribute->getAnnotationPropertiesToAttributeClasses() as $annotationPropertyToAttributeClass) {
            foreach ($arrayItemNodes as $key => $arrayItemNode) {
                if ($arrayItemNode->key !== $annotationPropertyToAttributeClass->getAnnotationProperty()) {
                    continue;
                }
                unset($arrayItemNodes[$key]);
            }
        }
        return $arrayItemNodes;
    }
    /**
     * @return AttributeGroup[]
     */
    private function createFromExplicitProperties(NestedAnnotationToAttribute $nestedAnnotationToAttribute, DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): array
    {
        $attributeGroups = [];
        foreach ($nestedAnnotationToAttribute->getAnnotationPropertiesToAttributeClasses() as $annotationPropertyToAttributeClass) {
            /** @var string $annotationProperty */
            $annotationProperty = $annotationPropertyToAttributeClass->getAnnotationProperty();
            $nestedArrayItemNode = $doctrineAnnotationTagValueNode->getValue($annotationProperty);
            if (!$nestedArrayItemNode instanceof ArrayItemNode) {
                continue;
            }
            if (!$nestedArrayItemNode->value instanceof CurlyListNode) {
                throw new ShouldNotHappenException();
            }
            foreach ($nestedArrayItemNode->value->getValues() as $arrayItemNode) {
                $nestedDoctrineAnnotationTagValueNode = $arrayItemNode->value;
                if (!$nestedDoctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
                    Assert::string($nestedDoctrineAnnotationTagValueNode);
                    $match = Strings::match($nestedDoctrineAnnotationTagValueNode, DoctrineAnnotationDecorator::LONG_ANNOTATION_REGEX);
                    if (!isset($match['class_name'])) {
                        throw new ShouldNotHappenException();
                    }
                    $identifierTypeNode = new IdentifierTypeNode($match['class_name']);
                    $identifierTypeNode->setAttribute(PhpDocAttributeKey::RESOLVED_CLASS, $match['class_name']);
                    $annotationContent = $match['annotation_content'] ?? '';
                    $nestedTokenIterator = $this->tokenIteratorFactory->create($annotationContent);
                    // mimics doctrine behavior just in phpdoc-parser syntax :)
                    // https://github.com/doctrine/annotations/blob/c66f06b7c83e9a2a7523351a9d5a4b55f885e574/lib/Doctrine/Common/Annotations/DocParser.php#L742
                    $values = $this->staticDoctrineAnnotationParser->resolveAnnotationMethodCall($nestedTokenIterator, new Nop());
                    $nestedDoctrineAnnotationTagValueNode = new DoctrineAnnotationTagValueNode($identifierTypeNode, $match['annotation_content'] ?? '', $values);
                }
                $attributeArgs = $this->createAttributeArgs($nestedDoctrineAnnotationTagValueNode);
                $originalIdentifier = $nestedDoctrineAnnotationTagValueNode->identifierTypeNode->name;
                $attributeName = $this->resolveAliasedAttributeName($originalIdentifier, $annotationPropertyToAttributeClass);
                if ($annotationPropertyToAttributeClass->doesNeedNewImport() && count($attributeName->getParts()) === 1) {
                    $attributeName->setAttribute(AttributeKey::EXTRA_USE_IMPORT, $annotationPropertyToAttributeClass->getAttributeClass());
                }
                $attribute = new Attribute($attributeName, $attributeArgs);
                $attributeGroups[] = new AttributeGroup([$attribute]);
            }
        }
        return $attributeGroups;
    }
}
