<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final class AnnotationPropertyToAttributeClass
{
    /**
     * @param string|int|null $annotationProperty
     */
    public function __construct(private readonly string $attributeClass, /**
     * @readonly
     */
    private $annotationProperty = null, private readonly bool $doesNeedNewImport = \false)
    {
        RectorAssert::className($this->attributeClass);
    }
    /**
     * @return string|int|null
     */
    public function getAnnotationProperty()
    {
        return $this->annotationProperty;
    }
    public function getAttributeClass(): string
    {
        return $this->attributeClass;
    }
    public function doesNeedNewImport(): bool
    {
        return $this->doesNeedNewImport;
    }
}
