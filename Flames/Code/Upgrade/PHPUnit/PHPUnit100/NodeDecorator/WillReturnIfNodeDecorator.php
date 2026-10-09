<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\PHPUnit100\NodeDecorator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
final class WillReturnIfNodeDecorator
{
    public function decorate(Closure $callbackClosure, ?MethodCall $willReturnOnConsecutiveMethodCall): void
    {
        if (!$willReturnOnConsecutiveMethodCall instanceof MethodCall) {
            return;
        }
        foreach ($callbackClosure->stmts as $key => $stmt) {
            if (!$stmt instanceof If_) {
                continue;
            }
            $currentArg = $willReturnOnConsecutiveMethodCall->getArgs()[$key];
            $stmt->stmts[] = new Return_($currentArg->value);
        }
    }
}
