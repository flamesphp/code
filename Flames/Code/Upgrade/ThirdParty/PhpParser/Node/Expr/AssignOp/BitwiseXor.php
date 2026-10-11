<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;
class BitwiseXor extends AssignOp
{
    public function getType(): string
    {
        return 'Expr_AssignOp_BitwiseXor';
    }
}
