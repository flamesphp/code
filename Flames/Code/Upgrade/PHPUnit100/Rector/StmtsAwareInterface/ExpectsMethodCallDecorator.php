<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit100\Rector\StmtsAwareInterface;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PHPUnit\Enum\ConsecutiveVariable;
final readonly class ExpectsMethodCallDecorator
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * Replace $this->expects(...)
     * with
     * $expects = ...
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|null
     */
    public function decorate(Expression $expression)
    {
        /** @var MethodCall|StaticCall|null $expectsExactlyCall */
        $expectsExactlyCall = null;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($expression, function (Node $node) use (&$expectsExactlyCall): ?MethodCall {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->name, 'expects')) {
                return null;
            }
            if ($node->isFirstClassCallable()) {
                return null;
            }
            $firstArg = $node->getArgs()[0];
            if (!$firstArg->value instanceof MethodCall && !$firstArg->value instanceof StaticCall) {
                return null;
            }
            $expectsExactlyCall = $firstArg->value;
            $node->args = [new Arg(new Variable(ConsecutiveVariable::MATCHER))];
            return $node;
        });
        // add expects() method
        if (!$expectsExactlyCall instanceof Expr) {
            $this->simpleCallableNodeTraverser->traverseNodesWithCallable($expression, function (Node $node): ?int {
                if (!$node instanceof MethodCall) {
                    return null;
                }
                if ($node->var instanceof MethodCall) {
                    return null;
                }
                $node->var = new MethodCall($node->var, 'expects', [new Arg(new Variable(ConsecutiveVariable::MATCHER))]);
                return NodeVisitor::STOP_TRAVERSAL;
            });
        }
        return $expectsExactlyCall;
    }
}
