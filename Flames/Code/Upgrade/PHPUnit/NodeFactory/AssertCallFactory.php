<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
final class AssertCallFactory
{
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall
     */
    public function createCallWithName($node, string $name)
    {
        if ($node instanceof MethodCall) {
            return new MethodCall($node->var, $name);
        }
        return new StaticCall($node->class, $name);
    }
}
