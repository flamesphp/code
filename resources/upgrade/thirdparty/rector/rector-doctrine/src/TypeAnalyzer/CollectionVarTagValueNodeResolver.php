<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\TypeAnalyzer;

use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Doctrine\CodeQuality\Enum\CollectionMapping;
use Flames\Code\Upgrade\Doctrine\NodeAnalyzer\AttributeFinder;
final readonly class CollectionVarTagValueNodeResolver
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private AttributeFinder $attributeFinder)
    {
    }
    public function resolve(Property $property): ?VarTagValueNode
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        if (!$this->hasAnnotationOrAttributeToMany($phpDocInfo, $property)) {
            return null;
        }
        return $phpDocInfo->getVarTagValueNode();
    }
    private function hasAnnotationOrAttributeToMany(PhpDocInfo $phpDocInfo, Property $property): bool
    {
        if ($phpDocInfo->hasByAnnotationClasses(CollectionMapping::TO_MANY_CLASSES)) {
            return \true;
        }
        return $this->attributeFinder->hasAttributeByClasses($property, CollectionMapping::TO_MANY_CLASSES);
    }
}
