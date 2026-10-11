<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\AnnotationsToAttributes\ValueObject;

use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitAttribute;
final readonly class RequiresAttributeAndValue
{
    /**
     * @param PHPUnitAttribute::* $attributeClass
     * @param string[] $value
     */
    public function __construct(private string $attributeClass, private array $value)
    {
    }
    /**
     * @return PHPUnitAttribute::*
     */
    public function getAttributeClass(): string
    {
        return $this->attributeClass;
    }
    /**
     * @return string[]
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
