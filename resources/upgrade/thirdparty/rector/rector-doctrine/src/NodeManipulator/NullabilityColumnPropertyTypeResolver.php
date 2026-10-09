<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\NodeManipulator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Doctrine\Enum\MappingClass;
use Flames\Code\Upgrade\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class NullabilityColumnPropertyTypeResolver
{
    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html#doctrine-mapping-types
     */
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private AttributeFinder $attributeFinder, private ValueResolver $valueResolver)
    {
    }
    public function isNullable(Property $property): bool
    {
        $nullableExpr = $this->attributeFinder->findAttributeByClassArgByName($property, MappingClass::COLUMN, 'nullable');
        if ($nullableExpr instanceof Expr) {
            return $this->valueResolver->isTrue($nullableExpr);
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        return $this->isNullableColumn($phpDocInfo);
    }
    private function isNullableColumn(PhpDocInfo $phpDocInfo): bool
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass(MappingClass::COLUMN);
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return \true;
        }
        $nullableValueArrayItemNode = $doctrineAnnotationTagValueNode->getValue('nullable');
        if (!$nullableValueArrayItemNode instanceof ArrayItemNode) {
            return \true;
        }
        return $nullableValueArrayItemNode->value instanceof ConstExprTrueNode;
    }
}
