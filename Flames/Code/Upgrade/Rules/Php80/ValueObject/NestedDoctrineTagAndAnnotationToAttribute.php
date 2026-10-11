<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
final readonly class NestedDoctrineTagAndAnnotationToAttribute
{
    public function __construct(private DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, private \Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedAnnotationToAttribute $nestedAnnotationToAttribute)
    {
    }
    public function getDoctrineAnnotationTagValueNode(): DoctrineAnnotationTagValueNode
    {
        return $this->doctrineAnnotationTagValueNode;
    }
    public function getNestedAnnotationToAttribute(): \Flames\Code\Upgrade\Rules\Php80\ValueObject\NestedAnnotationToAttribute
    {
        return $this->nestedAnnotationToAttribute;
    }
}
