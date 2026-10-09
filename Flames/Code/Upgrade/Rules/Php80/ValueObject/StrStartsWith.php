<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
final readonly class StrStartsWith
{
    public function __construct(private FuncCall $funcCall, private Expr $haystackExpr, private Expr $needleExpr, private bool $isPositive)
    {
    }
    public function getFuncCall(): FuncCall
    {
        return $this->funcCall;
    }
    public function getHaystackExpr(): Expr
    {
        return $this->haystackExpr;
    }
    public function isPositive(): bool
    {
        return $this->isPositive;
    }
    public function getNeedleExpr(): Expr
    {
        return $this->needleExpr;
    }
}
