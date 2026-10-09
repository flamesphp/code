<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\VariableAndCallForeach;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ForeachMatcher
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private \Flames\Code\Upgrade\Rules\Naming\Matcher\CallMatcher $callMatcher)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function match(Foreach_ $foreach, $functionLike): ?VariableAndCallForeach
    {
        $call = $this->callMatcher->matchCall($foreach);
        if (!$call instanceof Node) {
            return null;
        }
        if (!$foreach->valueVar instanceof Variable) {
            return null;
        }
        $variableName = $this->nodeNameResolver->getName($foreach->valueVar);
        if ($variableName === null) {
            return null;
        }
        return new VariableAndCallForeach($foreach->valueVar, $call, $variableName, $functionLike);
    }
}
