<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
final readonly class MethodCallRemover
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function removeMethodCall(Expression $expression, string $methodName): void
    {
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($expression, function (Node $node) use ($methodName): ?Node {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->name, $methodName)) {
                return null;
            }
            return $node->var;
        });
    }
}
