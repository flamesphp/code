<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\Rules\Php81\Enum\AttributeName;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AttributeArrayNameInliner;
/**
 * @see \Flames\Code\Upgrade\Tests\PhpAttribute\Printer\PhpAttributeGroupFactoryTest
 */
final readonly class PhpAttributeGroupFactory
{
    public function __construct(private AnnotationToAttributeMapper $annotationToAttributeMapper, private \Flames\Code\Upgrade\PhpAttribute\NodeFactory\AttributeNameFactory $attributeNameFactory, private \Flames\Code\Upgrade\PhpAttribute\NodeFactory\NamedArgsFactory $namedArgsFactory, private \Flames\Code\Upgrade\PhpAttribute\NodeFactory\AnnotationToAttributeIntegerValueCaster $annotationToAttributeIntegerValueCaster, private AttributeArrayNameInliner $attributeArrayNameInliner)
    {
    }
    public function createFromSimpleTag(AnnotationToAttribute $annotationToAttribute, ?string $value = null): AttributeGroup
    {
        return $this->createFromClass($annotationToAttribute->getAttributeClass(), $value);
    }
    /**
     * @param AttributeName::*|string $attributeClass
     */
    public function createFromClass(string $attributeClass, ?string $value = null): AttributeGroup
    {
        $fullyQualified = new FullyQualified($attributeClass);
        $attribute = new Attribute($fullyQualified);
        if ($value !== null && $value !== '') {
            $arg = new Arg(new String_($value));
            $attribute->args = [$arg];
        }
        return new AttributeGroup([$attribute]);
    }
    /**
     * @api tests
     * @param mixed[] $items
     */
    public function createFromClassWithItems(string $attributeClass, array $items): AttributeGroup
    {
        $fullyQualified = new FullyQualified($attributeClass);
        $args = $this->createArgsFromItems($items);
        $attribute = new Attribute($fullyQualified, $args);
        return new AttributeGroup([$attribute]);
    }
    /**
     * @param Use_[] $uses
     */
    public function create(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, AnnotationToAttribute $annotationToAttribute, array $uses): AttributeGroup
    {
        $values = $doctrineAnnotationTagValueNode->getValuesWithSilentKey();
        $args = $this->createArgsFromItems($values, '', $annotationToAttribute->getClassReferenceFields());
        $this->annotationToAttributeIntegerValueCaster->castAttributeTypes($annotationToAttribute, $args);
        $args = $this->attributeArrayNameInliner->inlineArrayToArgs($args, $annotationToAttribute->getAttributeClass());
        $attributeName = $this->attributeNameFactory->create($annotationToAttribute, $doctrineAnnotationTagValueNode, $uses);
        // keep FQN in the attribute, so it can be easily detected later
        $attributeName->setAttribute(AttributeKey::PHP_ATTRIBUTE_NAME, $annotationToAttribute->getAttributeClass());
        $attribute = new Attribute($attributeName, $args);
        return new AttributeGroup([$attribute]);
    }
    /**
     * @api tests
     *
     * @param ArrayItemNode[]|mixed[] $items
     * @param string $attributeClass @deprecated
     * @param string[] $classReferencedFields
     *
     * @return list<Arg>
     */
    public function createArgsFromItems(array $items, string $attributeClass = '', array $classReferencedFields = []): array
    {
        $mappedItems = $this->annotationToAttributeMapper->map($items);
        $this->mapClassReferences($mappedItems, $classReferencedFields);
        $values = $mappedItems instanceof Array_ ? $mappedItems->items : $mappedItems;
        // the key here should contain the named argument
        return $this->namedArgsFactory->createFromValues($values);
    }
    /**
     * @param string[] $classReferencedFields
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|string $expr
     */
    private function mapClassReferences($expr, array $classReferencedFields): void
    {
        if (!$expr instanceof Array_) {
            return;
        }
        foreach ($expr->items as $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                continue;
            }
            if (!$arrayItem->key instanceof String_) {
                continue;
            }
            if (!in_array($arrayItem->key->value, $classReferencedFields)) {
                continue;
            }
            if ($arrayItem->value instanceof ClassConstFetch) {
                continue;
            }
            if (!$arrayItem->value instanceof String_) {
                continue;
            }
            if ($arrayItem->value->value === '') {
                continue;
            }
            $arrayItem->value = new ClassConstFetch(new FullyQualified($arrayItem->value->value), 'class');
        }
    }
}
