<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Matcher;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
final class CallMatcher
{
    /**
     * @return FuncCall|StaticCall|MethodCall|null
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $node
     */
    public function matchCall($node): ?Node
    {
        if ($node->expr instanceof MethodCall) {
            return $node->expr;
        }
        if ($node->expr instanceof StaticCall) {
            return $node->expr;
        }
        if ($node->expr instanceof FuncCall) {
            return $node->expr;
        }
        return null;
    }
}
