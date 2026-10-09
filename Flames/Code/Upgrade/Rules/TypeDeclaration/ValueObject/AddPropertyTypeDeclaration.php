<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\ValueObject;

use PHPStan\Type\Type;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class AddPropertyTypeDeclaration
{
    public function __construct(private string $class, private string $propertyName, private Type $type)
    {
        RectorAssert::className($this->class);
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
    public function getType(): Type
    {
        return $this->type;
    }
}
