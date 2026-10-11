<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
final class ArgumentMover
{
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    public function removeFirstArg($node): void
    {
        if ($node->isFirstClassCallable()) {
            return;
        }
        $methodArguments = $node->getArgs();
        array_shift($methodArguments);
        $node->args = $methodArguments;
    }
}
