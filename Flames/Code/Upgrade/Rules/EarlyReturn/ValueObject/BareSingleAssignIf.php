<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\EarlyReturn\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
final readonly class BareSingleAssignIf
{
    public function __construct(private If_ $if, private Assign $assign)
    {
    }
    public function getIfCondExpr(): Expr
    {
        return $this->if->cond;
    }
    public function getIf(): If_
    {
        return $this->if;
    }
    public function getAssign(): Assign
    {
        return $this->assign;
    }
}
