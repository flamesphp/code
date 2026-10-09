<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
final readonly class FuncCallAndExpr
{
    public function __construct(private FuncCall $funcCall, private Expr $expr)
    {
    }
    public function getFuncCall(): FuncCall
    {
        return $this->funcCall;
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
}
