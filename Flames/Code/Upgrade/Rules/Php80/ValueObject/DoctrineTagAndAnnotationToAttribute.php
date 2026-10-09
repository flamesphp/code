<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
final readonly class DoctrineTagAndAnnotationToAttribute
{
    public function __construct(private DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, private \Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute $annotationToAttribute)
    {
    }
    public function getDoctrineAnnotationTagValueNode(): DoctrineAnnotationTagValueNode
    {
        return $this->doctrineAnnotationTagValueNode;
    }
    public function getAnnotationToAttribute(): \Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute
    {
        return $this->annotationToAttribute;
    }
}
