<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObjectFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\StrStartsWith;
final class StrStartsWithFactory
{
    public function createFromFuncCall(FuncCall $funcCall, bool $isPositive): ?StrStartsWith
    {
        if ($funcCall->isFirstClassCallable()) {
            return null;
        }
        if (count($funcCall->getArgs()) < 2) {
            return null;
        }
        $haystack = $funcCall->getArgs()[0]->value;
        $needle = $funcCall->getArgs()[1]->value;
        return new StrStartsWith($funcCall, $haystack, $needle, $isPositive);
    }
}
