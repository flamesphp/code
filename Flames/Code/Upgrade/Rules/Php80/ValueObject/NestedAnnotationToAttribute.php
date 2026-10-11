<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\Rules\Php80\Contract\ValueObject\AnnotationToAttributeInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
final class NestedAnnotationToAttribute implements AnnotationToAttributeInterface
{
    /**
     * @var AnnotationPropertyToAttributeClass[]
     */
    private array $annotationPropertiesToAttributeClasses;
    /**
     * @param array<string, string>|string[]|AnnotationPropertyToAttributeClass[] $annotationPropertiesToAttributeClasses
     */
    public function __construct(private readonly string $tag, array $annotationPropertiesToAttributeClasses, private readonly bool $removeOriginal = \false)
    {
        RectorAssert::className($this->tag);
        // back compatibility for raw scalar values
        foreach ($annotationPropertiesToAttributeClasses as $annotationProperty => $attributeClass) {
            if ($attributeClass instanceof \Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationPropertyToAttributeClass) {
                $this->annotationPropertiesToAttributeClasses[] = $attributeClass;
            } else {
                $this->annotationPropertiesToAttributeClasses[] = new \Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationPropertyToAttributeClass($attributeClass, $annotationProperty);
            }
        }
    }
    public function getTag(): string
    {
        return $this->tag;
    }
    /**
     * @return AnnotationPropertyToAttributeClass[]
     */
    public function getAnnotationPropertiesToAttributeClasses(): array
    {
        return $this->annotationPropertiesToAttributeClasses;
    }
    public function getAttributeClass(): string
    {
        return $this->tag;
    }
    public function shouldRemoveOriginal(): bool
    {
        return $this->removeOriginal;
    }
    public function hasExplicitParameters(): bool
    {
        $found = array_any($this->annotationPropertiesToAttributeClasses, fn($annotationPropertyToAttributeClass) => is_string($annotationPropertyToAttributeClass->getAnnotationProperty()));
        return $found;
    }
}
