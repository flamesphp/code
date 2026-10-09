<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\NodeManipulator;

use FlamesPrefix202610\Nette\Utils\Strings;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\ClassAnnotationMatcher;
use Flames\Code\Upgrade\Doctrine\CodeQuality\Enum\CollectionMapping;
use Flames\Code\Upgrade\Doctrine\CodeQuality\Enum\EntityMappingKey;
use Flames\Code\Upgrade\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\Doctrine\NodeAnalyzer\TargetEntityResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class ToOneRelationPropertyTypeResolver
{
    public function __construct(private TypeFactory $typeFactory, private PhpDocInfoFactory $phpDocInfoFactory, private ClassAnnotationMatcher $classAnnotationMatcher, private AttributeFinder $attributeFinder, private TargetEntityResolver $targetEntityResolver)
    {
    }
    public function resolve(Property $property): ?Type
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClasses(CollectionMapping::TO_ONE_CLASSES);
        if ($doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return $this->processToOneRelation($property, $doctrineAnnotationTagValueNode);
        }
        $expr = $this->attributeFinder->findAttributeByClassesArgByName($property, CollectionMapping::TO_ONE_CLASSES, EntityMappingKey::TARGET_ENTITY);
        if (!$expr instanceof Expr) {
            return null;
        }
        $targetEntityClass = $this->targetEntityResolver->resolveFromExpr($expr);
        if ($targetEntityClass !== null) {
            return $this->resolveNullableObjectType(new FullyQualifiedObjectType($targetEntityClass));
        }
        return null;
    }
    private function processToOneRelation(Property $property, DoctrineAnnotationTagValueNode $toOneDoctrineAnnotationTagValueNode): Type
    {
        $targetEntityArrayItemNode = $toOneDoctrineAnnotationTagValueNode->getValue(EntityMappingKey::TARGET_ENTITY);
        if (!$targetEntityArrayItemNode instanceof ArrayItemNode) {
            return new MixedType();
        }
        $targetEntityClass = $targetEntityArrayItemNode->value;
        if ($targetEntityClass instanceof StringNode) {
            $targetEntityClass = $targetEntityClass->value;
        }
        if (!is_string($targetEntityClass)) {
            return new MixedType();
        }
        if (str_ends_with($targetEntityClass, '::class')) {
            $targetEntityClass = Strings::before($targetEntityClass, '::class');
        }
        // resolve to FQN
        $tagFullyQualifiedName = $this->classAnnotationMatcher->resolveTagFullyQualifiedName($targetEntityClass, $property);
        return $this->resolveNullableObjectType(new FullyQualifiedObjectType($tagFullyQualifiedName));
    }
    /**
     * The relation is always nullable, as the entity can be created without the relation being set yet
     */
    private function resolveNullableObjectType(FullyQualifiedObjectType $fullyQualifiedObjectType): Type
    {
        return $this->typeFactory->createMixedPassedOrUnionType([$fullyQualifiedObjectType, new NullType()]);
    }
}
