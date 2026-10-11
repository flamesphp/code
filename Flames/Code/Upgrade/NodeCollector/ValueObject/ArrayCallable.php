<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeCollector\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\Validation\RectorAssert;
final readonly class ArrayCallable
{
    public function __construct(private Expr $callerExpr, private string $class, private string $method)
    {
        RectorAssert::className($this->class);
    }
    public function getClass(): string
    {
        return $this->class;
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getCallerExpr(): Expr
    {
        return $this->callerExpr;
    }
}
