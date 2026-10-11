<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpDocParser\NodeTraverser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\PhpDocParser\NodeVisitor\CallableNodeVisitor;
/**
 * @api
 */
final class SimpleCallableNodeTraverser
{
    /**
     * @param Node|Node[]|null $node
     *
     * @param callable(Node $node): (int|Node|null|Node[]) $callable
     * @api shortcut helper
     */
    public static function traverse($node, callable $callable): void
    {
        self::traverseNodesWithCallable($node, $callable);
    }
    /**
     * @param callable(Node $node): (int|Node|null|Node[]) $callable
     * @param Node|Node[]|null $node
     */
    public static function traverseNodesWithCallable($node, callable $callable): void
    {
        if ($node === null || $node === []) {
            return;
        }
        $callableNodeVisitor = new CallableNodeVisitor($callable);
        $nodeTraverser = new NodeTraverser($callableNodeVisitor);
        $nodes = $node instanceof Node ? [$node] : $node;
        $nodeTraverser->traverse($nodes);
    }
}
