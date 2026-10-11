<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class RenameProperty
{
    public function __construct(private string $type, private string $oldProperty, private string $newProperty)
    {
        RectorAssert::className($this->type);
        RectorAssert::propertyName($this->oldProperty);
        RectorAssert::propertyName($this->newProperty);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->type);
    }
    public function getOldProperty(): string
    {
        return $this->oldProperty;
    }
    public function getNewProperty(): string
    {
        return $this->newProperty;
    }
}
