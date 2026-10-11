<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php72\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\List_;
final readonly class ListAndEach
{
    public function __construct(private List_ $list, private FuncCall $eachFuncCall)
    {
    }
    public function getList(): List_
    {
        return $this->list;
    }
    public function getEachFuncCall(): FuncCall
    {
        return $this->eachFuncCall;
    }
}
