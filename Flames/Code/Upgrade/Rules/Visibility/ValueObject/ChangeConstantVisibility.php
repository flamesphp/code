<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Visibility\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class ChangeConstantVisibility
{
    public function __construct(private string $class, private string $constant, private int $visibility)
    {
        RectorAssert::className($this->class);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->class);
    }
    public function getConstant(): string
    {
        return $this->constant;
    }
    public function getVisibility(): int
    {
        return $this->visibility;
    }
}
