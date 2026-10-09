<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
final readonly class SymfonyPhpClosureDetector
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser)
    {
    }
    public function detect(Closure $closure): bool
    {
        if (count($closure->params) !== 1) {
            return \false;
        }
        $firstParam = $closure->params[0];
        if (!$firstParam->type instanceof FullyQualified) {
            return \false;
        }
        return $this->nodeNameResolver->isName($firstParam->type, SymfonyClass::CONTAINER_CONFIGURATOR);
    }
    public function hasDefaultsConfigured(Closure $closure, string $desiredMethodName): bool
    {
        $hasDefaultsAutoconfigure = \false;
        // has defaults autoconfigure?
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($closure, function (Node $node) use (&$hasDefaultsAutoconfigure, $desiredMethodName): ?int {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->name, $desiredMethodName)) {
                return null;
            }
            /** @var MethodCall[] $methodCalls */
            $methodCalls = $this->betterNodeFinder->findInstanceOf($node, MethodCall::class);
            foreach ($methodCalls as $methodCall) {
                if (!$this->nodeNameResolver->isName($methodCall->name, 'defaults')) {
                    continue;
                }
                $hasDefaultsAutoconfigure = \true;
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            return null;
        });
        return $hasDefaultsAutoconfigure;
    }
}
