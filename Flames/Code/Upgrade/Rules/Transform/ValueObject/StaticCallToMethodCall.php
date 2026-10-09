<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class StaticCallToMethodCall
{
    public function __construct(private string $staticClass, private string $staticMethod, private string $classType, private string $methodName)
    {
        RectorAssert::className($this->staticClass);
        // special char to match all method names
        if ($this->staticMethod !== '*') {
            RectorAssert::methodName($this->staticMethod);
        }
        RectorAssert::className($this->classType);
        if ($this->methodName !== '*') {
            RectorAssert::methodName($this->methodName);
        }
    }
    public function getClassObjectType(): ObjectType
    {
        return new ObjectType($this->classType);
    }
    public function getClassType(): string
    {
        return $this->classType;
    }
    public function getMethodName(): string
    {
        return $this->methodName;
    }
    public function isStaticCallMatch(StaticCall $staticCall): bool
    {
        if (!$staticCall->class instanceof Name) {
            return \false;
        }
        $staticCallClassName = $staticCall->class->toString();
        if ($staticCallClassName !== $this->staticClass) {
            return \false;
        }
        if (!$staticCall->name instanceof Identifier) {
            return \false;
        }
        // all methods
        if ($this->staticMethod === '*') {
            return \true;
        }
        $staticCallMethodName = $staticCall->name->toString();
        return $staticCallMethodName === $this->staticMethod;
    }
}
