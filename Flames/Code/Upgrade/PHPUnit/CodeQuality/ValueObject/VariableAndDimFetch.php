<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
final readonly class VariableAndDimFetch
{
    public function __construct(private Variable $variable, private Expr $dimFetchExpr)
    {
    }
    public function getVariable(): Variable
    {
        return $this->variable;
    }
    public function getDimFetchExpr(): Expr
    {
        return $this->dimFetchExpr;
    }
}
