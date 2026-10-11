<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class NewToStaticCall
{
    public function __construct(private string $type, private string $staticCallClass, private string $staticCallMethod)
    {
        RectorAssert::className($this->type);
        RectorAssert::className($this->staticCallClass);
        RectorAssert::methodName($this->staticCallMethod);
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->type);
    }
    public function getStaticCallClass(): string
    {
        return $this->staticCallClass;
    }
    public function getStaticCallMethod(): string
    {
        return $this->staticCallMethod;
    }
}
