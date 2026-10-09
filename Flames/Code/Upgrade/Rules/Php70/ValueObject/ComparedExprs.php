<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\ValueObject;

use PhpParser\Node\Expr;
final readonly class ComparedExprs
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
