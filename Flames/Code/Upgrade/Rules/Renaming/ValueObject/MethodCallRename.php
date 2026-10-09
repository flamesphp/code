<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Renaming\Contract\MethodCallRenameInterface;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class MethodCallRename implements MethodCallRenameInterface
{
    public function __construct(private string $class, private string $oldMethod, private string $newMethod)
    {
        RectorAssert::className($this->class);
        RectorAssert::methodName($this->oldMethod);
        RectorAssert::methodName($this->newMethod);
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->class);
    }
    public function getOldMethod(): string
    {
        return $this->oldMethod;
    }
    public function getNewMethod(): string
    {
        return $this->newMethod;
    }
}
