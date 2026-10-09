<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php71\ValueObject;

use PhpParser\Node\Expr;
final readonly class TwoNodeMatch
{
    public function __construct(private Expr $firstExpr, private Expr $secondExpr)
    {
    }
    public function getFirstExpr(): Expr
    {
        return $this->firstExpr;
    }
    public function getSecondExpr(): Expr
    {
        return $this->secondExpr;
    }
}
