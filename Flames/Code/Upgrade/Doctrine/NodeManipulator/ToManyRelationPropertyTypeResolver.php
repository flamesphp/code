<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\ThirdParty\Doctrine\CodeQuality\Enum\CollectionMapping;
use Flames\Code\Upgrade\ThirdParty\Doctrine\CodeQuality\Enum\DocumentMappingKey;
use Flames\Code\Upgrade\ThirdParty\Doctrine\CodeQuality\Enum\EntityMappingKey;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\ThirdParty\Doctrine\PhpDoc\ShortClassExpander;
use Flames\Code\Upgrade\ThirdParty\Doctrine\TypeAnalyzer\CollectionTypeFactory;
use Flames\Code\Upgrade\ThirdParty\Doctrine\TypeAnalyzer\CollectionTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class ToManyRelationPropertyTypeResolver
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private ShortClassExpander $shortClassExpander, private AttributeFinder $attributeFinder, private ValueResolver $valueResolver, private CollectionTypeFactory $collectionTypeFactory, private CollectionTypeResolver $collectionTypeResolver)
    {
    }
    public function resolve(Property $property): ?Type
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClasses(CollectionMapping::TO_MANY_CLASSES);
        if ($doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return $this->processToManyRelation($property, $doctrineAnnotationTagValueNode);
        }
        $expr = $this->attributeFinder->findAttributeByClassesArgByNames($property, CollectionMapping::TO_MANY_CLASSES, [EntityMappingKey::TARGET_ENTITY, DocumentMappingKey::TARGET_DOCUMENT]);
        if (!$expr instanceof Expr) {
            return null;
        }
        return $this->resolveTypeFromTargetEntity($expr, $property);
    }
    private function processToManyRelation(Property $property, DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): ?\PHPStan\Type\Type
    {
        $targetEntityArrayItemNode = $doctrineAnnotationTagValueNode->getValue(EntityMappingKey::TARGET_ENTITY) ?: $doctrineAnnotationTagValueNode->getValue(DocumentMappingKey::TARGET_DOCUMENT);
        if (!$targetEntityArrayItemNode instanceof ArrayItemNode) {
            // most likely mapped superclass
            return new ObjectType(DoctrineClass::COLLECTION);
        }
        $targetEntityClass = $targetEntityArrayItemNode->value;
        if ($targetEntityClass instanceof StringNode) {
            $targetEntityClass = $targetEntityClass->value;
        }
        if (!is_string($targetEntityClass)) {
            return null;
        }
        return $this->resolveTypeFromTargetEntity($targetEntityClass, $property);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|string $targetEntity
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $property
     */
    private function resolveTypeFromTargetEntity($targetEntity, $property): Type
    {
        if ($targetEntity instanceof Expr) {
            $targetEntity = $this->valueResolver->getValue($targetEntity);
        }
        if (!is_string($targetEntity)) {
            return new FullyQualifiedObjectType(DoctrineClass::COLLECTION);
        }
        $entityFullyQualifiedClass = $this->shortClassExpander->resolveFqnTargetEntity($targetEntity, $property);
        $fullyQualifiedObjectType = new FullyQualifiedObjectType($entityFullyQualifiedClass);
        return $this->collectionTypeFactory->createType($fullyQualifiedObjectType, $this->collectionTypeResolver->hasIndexBy($property), $property);
    }
}
