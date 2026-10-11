<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
/**
 * @api
 */
final readonly class AttrinationFinder
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private \Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttributeFinder $attributeFinder)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     * @return \Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute|null
     */
    public function getByOne($node, string $name)
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if ($phpDocInfo instanceof PhpDocInfo && $phpDocInfo->hasByAnnotationClass($name)) {
            return $phpDocInfo->getByAnnotationClass($name);
        }
        return $this->attributeFinder->findAttributeByClass($node, $name);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function hasByOne($node, string $name): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if ($phpDocInfo instanceof PhpDocInfo && $phpDocInfo->hasByAnnotationClass($name)) {
            return \true;
        }
        $attribute = $this->attributeFinder->findAttributeByClass($node, $name);
        return $attribute instanceof Attribute;
    }
    /**
     * @return array<DoctrineAnnotationTagValueNode|Attribute>
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findManyBy($node, string $name): array
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        $doctrineAnnotationTagValueNodes = [];
        if ($phpDocInfo instanceof PhpDocInfo) {
            $doctrineAnnotationTagValueNodes = $phpDocInfo->findByAnnotationClass($name);
        }
        return array_merge($doctrineAnnotationTagValueNodes, $this->attributeFinder->findManyByClass($node, $name));
    }
    /**
     * @param string[] $names
     * @return array<DoctrineAnnotationTagValueNode|Attribute>
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param $node
     */
    public function findManyByMany($node, array $names): array
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        $doctrineAnnotationTagValueNodes = [];
        if ($phpDocInfo instanceof PhpDocInfo) {
            foreach ($names as $name) {
                foreach ($phpDocInfo->findByAnnotationClass($name) as $annotationTagValueNode) {
                    $doctrineAnnotationTagValueNodes[] = $annotationTagValueNode;
                }
            }
        }
        return array_merge($doctrineAnnotationTagValueNodes, $this->attributeFinder->findManyByClasses($node, $names));
    }
    /**
     * @param string[] $classNames
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property $property
     */
    public function hasByMany($property, array $classNames): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
        if ($phpDocInfo instanceof PhpDocInfo && $phpDocInfo->hasByAnnotationClasses($classNames)) {
            return \true;
        }
        $attribute = $this->attributeFinder->findAttributeByClasses($property, $classNames);
        return $attribute instanceof Attribute;
    }
    /**
     * @param string[] $classNames
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property $property
     * @return \Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute|null
     */
    public function getByMany($property, array $classNames)
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
        if ($phpDocInfo instanceof PhpDocInfo) {
            $foundDoctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClasses($classNames);
            if ($foundDoctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
                return $foundDoctrineAnnotationTagValueNode;
            }
        }
        return $this->attributeFinder->findAttributeByClasses($property, $classNames);
    }
}
