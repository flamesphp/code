<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeFactory;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
final readonly class AttrGroupsFactory
{
    public function __construct(private PhpAttributeGroupFactory $phpAttributeGroupFactory)
    {
    }
    /**
     * @param DoctrineTagAndAnnotationToAttribute[] $doctrineTagAndAnnotationToAttributes
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    public function create(array $doctrineTagAndAnnotationToAttributes, array $uses): array
    {
        $attributeGroups = [];
        foreach ($doctrineTagAndAnnotationToAttributes as $doctrineTagAndAnnotationToAttribute) {
            $doctrineAnnotationTagValueNode = $doctrineTagAndAnnotationToAttribute->getDoctrineAnnotationTagValueNode();
            // add attributes
            $attributeGroups[] = $this->phpAttributeGroupFactory->create($doctrineAnnotationTagValueNode, $doctrineTagAndAnnotationToAttribute->getAnnotationToAttribute(), $uses);
        }
        return $attributeGroups;
    }
}
