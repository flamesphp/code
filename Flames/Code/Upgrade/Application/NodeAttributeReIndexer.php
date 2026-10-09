<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Application;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TryCatch;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final class NodeAttributeReIndexer
{
    public static function reIndexNodeAttributes(Node $node): ?Node
    {
        self::reIndexStmtsKeys($node);
        if ($node instanceof If_) {
            $node->elseifs = array_values($node->elseifs);
            return $node;
        }
        if ($node instanceof TryCatch) {
            $node->catches = array_values($node->catches);
            return $node;
        }
        if ($node instanceof FunctionLike) {
            /** @var ClassMethod|Function_|Closure $node */
            $node->params = array_values($node->params);
            if ($node instanceof Closure) {
                $node->uses = array_values($node->uses);
            }
            return $node;
        }
        if ($node instanceof CallLike) {
            /** @var FuncCall|MethodCall|New_|NullsafeMethodCall|StaticCall $node */
            $node->args = array_values($node->args);
            return $node;
        }
        if ($node instanceof Switch_) {
            $node->cases = array_values($node->cases);
            return $node;
        }
        return null;
    }
    private static function reIndexStmtsKeys(Node $node): ?Node
    {
        if (!NodeGroup::isStmtAwareNode($node) && !$node instanceof ClassLike) {
            return null;
        }
        Assert::propertyExists($node, 'stmts');
        if ($node->stmts === null) {
            return null;
        }
        $node->stmts = array_values($node->stmts);
        return $node;
    }
}
