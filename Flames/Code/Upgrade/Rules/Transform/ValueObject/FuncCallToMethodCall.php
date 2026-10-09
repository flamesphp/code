<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
/**
 * @api used in deprecated FuncCallToMethodCallUpgrade configs
 */
final readonly class FuncCallToMethodCall
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
    public function getNewObjectType(): ObjectType
    {
        return new ObjectType($this->newClassName);
    }
    public function getNewMethodName(): string
    {
        return $this->newMethodName;
    }
}
