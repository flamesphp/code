<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\StrStartsWith;
final class StrStartsWithFuncCallFactory
{
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot
     */
    public function createStrStartsWith(StrStartsWith $strStartsWith)
    {
        $args = [new Arg($strStartsWith->getHaystackExpr()), new Arg($strStartsWith->getNeedleExpr())];
        $funcCall = new FuncCall(new Name('str_starts_with'), $args);
        if ($strStartsWith->isPositive()) {
            return $funcCall;
        }
        return new BooleanNot($funcCall);
    }
}
