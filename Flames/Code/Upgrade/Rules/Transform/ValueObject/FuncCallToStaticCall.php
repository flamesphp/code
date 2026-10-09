<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class FuncCallToStaticCall
{
    public function __construct(private string $oldFuncName, private string $newClassName, private string $newMethodName)
    {
        RectorAssert::functionName($this->oldFuncName);
        RectorAssert::className($this->newClassName);
        RectorAssert::methodName($this->newMethodName);
    }
    public function getOldFuncName(): string
    {
        return $this->oldFuncName;
    }
    public function getNewClassName(): string
    {
        return $this->newClassName;
    }
    public function getNewMethodName(): string
    {
        return $this->newMethodName;
    }
}
