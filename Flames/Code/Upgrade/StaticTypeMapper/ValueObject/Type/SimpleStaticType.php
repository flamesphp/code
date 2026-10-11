<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type;

use PHPStan\Type\StaticType;
final class SimpleStaticType extends StaticType
{
    public function __construct(private readonly string $className)
    {
    }
    public function getClassName(): string
    {
        return $this->className;
    }
}
