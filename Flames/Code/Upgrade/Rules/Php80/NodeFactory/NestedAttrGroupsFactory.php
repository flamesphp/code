<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeFactory;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedDoctrineTagAndAnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\NodeFactory\PhpNestedAttributeGroupFactory;
final readonly class NestedAttrGroupsFactory
{
    public function __construct(private PhpNestedAttributeGroupFactory $phpNestedAttributeGroupFactory)
    {
    }
    /**
     * @param NestedDoctrineTagAndAnnotationToAttribute[] $nestedDoctrineTagAndAnnotationToAttributes
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    public function create(array $nestedDoctrineTagAndAnnotationToAttributes, array $uses): array
    {
        $attributeGroups = [];
        foreach ($nestedDoctrineTagAndAnnotationToAttributes as $nestedDoctrineTagAndAnnotationToAttribute) {
            $doctrineAnnotationTagValueNode = $nestedDoctrineTagAndAnnotationToAttribute->getDoctrineAnnotationTagValueNode();
            $nestedAnnotationToAttribute = $nestedDoctrineTagAndAnnotationToAttribute->getNestedAnnotationToAttribute();
            // do not create alternative for the annotation, only unwrap
            if (!$nestedAnnotationToAttribute->shouldRemoveOriginal()) {
                // add attributes
                $attributeGroups[] = $this->phpNestedAttributeGroupFactory->create($doctrineAnnotationTagValueNode, $nestedDoctrineTagAndAnnotationToAttribute->getNestedAnnotationToAttribute(), $uses);
            }
            $nestedAttributeGroups = $this->phpNestedAttributeGroupFactory->createNested($doctrineAnnotationTagValueNode, $nestedDoctrineTagAndAnnotationToAttribute->getNestedAnnotationToAttribute());
            $attributeGroups = array_merge($attributeGroups, $nestedAttributeGroups);
        }
        return array_unique($attributeGroups, \SORT_REGULAR);
    }
}
