<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\Rules\Php80\Contract\ValueObject\AnnotationToAttributeInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class AnnotationToAttribute implements AnnotationToAttributeInterface
{
    /**
     * @param string[] $classReferenceFields
     */
    public function __construct(private string $tag, private ?string $attributeClass = null, /**
     * @readonly
     */
    private array $classReferenceFields = [], private bool $useValueAsAttributeArgument = \false)
    {
        RectorAssert::className($this->tag);
        if (is_string($this->attributeClass)) {
            RectorAssert::className($this->attributeClass);
        }
        Assert::allString($this->classReferenceFields);
    }
    public function getTag(): string
    {
        return $this->tag;
    }
    public function getAttributeClass(): string
    {
        if ($this->attributeClass === null) {
            return $this->tag;
        }
        return $this->attributeClass;
    }
    /**
     * @return string[]
     */
    public function getClassReferenceFields(): array
    {
        return $this->classReferenceFields;
    }
    public function getUseValueAsAttributeArgument(): bool
    {
        return $this->useValueAsAttributeArgument;
    }
}
