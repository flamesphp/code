<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
class NotEqual extends BinaryOp
{
    public function getOperatorSigil(): string
    {
        return '!=';
    }
    public function getType(): string
    {
        return 'Expr_BinaryOp_NotEqual';
    }
}
