<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\VariableAndCallAssign;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class VariableAndCallAssignMatcher
{
    public function __construct(private \Flames\Code\Upgrade\Rules\Naming\Matcher\CallMatcher $callMatcher, private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function match(Assign $assign, $functionLike): ?VariableAndCallAssign
    {
        $call = $this->callMatcher->matchCall($assign);
        if (!$call instanceof Node) {
            return null;
        }
        if (!$assign->var instanceof Variable) {
            return null;
        }
        $variableName = $this->nodeNameResolver->getName($assign->var);
        if ($variableName === null) {
            return null;
        }
        $isVariableFoundInCallArgs = (bool) $this->betterNodeFinder->findFirst($call->isFirstClassCallable() ? [] : $call->getArgs(), fn(Node $subNode): bool => $subNode instanceof Variable && $this->nodeNameResolver->isName($subNode, $variableName));
        if ($isVariableFoundInCallArgs) {
            return null;
        }
        return new VariableAndCallAssign($assign->var, $call, $assign, $variableName, $functionLike);
    }
}
