<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Guard;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class ParamTypeAddGuard
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private BetterNodeFinder $betterNodeFinder)
    {
    }
    public function isLegal(Param $param, FunctionLike $functionLike): bool
    {
        $paramName = $this->nodeNameResolver->getName($param);
        $isLegal = \true;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable((array) $functionLike->getStmts(), function (Node $subNode) use (&$isLegal, $paramName): ?int {
            if ($subNode instanceof Assign && $subNode->var instanceof Variable && $this->nodeNameResolver->isName($subNode->var, $paramName)) {
                $isLegal = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($subNode instanceof If_ && (bool) $this->betterNodeFinder->findFirst($subNode->cond, fn(Node $node): bool => $node instanceof Variable && $this->nodeNameResolver->isName($node, $paramName))) {
                $isLegal = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if ($subNode instanceof Ternary && (bool) $this->betterNodeFinder->findFirst($subNode, fn(Node $node): bool => $node instanceof Variable && $this->nodeNameResolver->isName($node, $paramName))) {
                $isLegal = \false;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $isLegal;
    }
}
