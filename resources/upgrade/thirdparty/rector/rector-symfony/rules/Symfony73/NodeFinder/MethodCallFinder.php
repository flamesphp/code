<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Symfony73\NodeFinder;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
final readonly class MethodCallFinder
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser)
    {
    }
    /**
     * @return MethodCall[]
     */
    public function find(ClassMethod $classMethod, string $desiredMethodName): array
    {
        $calls = [];
        $shouldReverse = \false;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classMethod, function (Node $node) use (&$calls, $desiredMethodName, &$shouldReverse) {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$node->name instanceof Identifier) {
                return null;
            }
            if ($node->name->toString() === $desiredMethodName) {
                if ($node->var instanceof MethodCall) {
                    $shouldReverse = \true;
                }
                $calls[] = $node;
            }
            return null;
        });
        return $shouldReverse ? array_reverse($calls) : $calls;
    }
}
