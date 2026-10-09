<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Yield_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
final readonly class YieldNodeFinder
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser)
    {
    }
    /**
     * @return Yield_[]|YieldFrom[]
     */
    public function find(FunctionLike $functionLike): array
    {
        $yieldNodes = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable((array) $functionLike->getStmts(), static function (Node $node) use (&$yieldNodes): ?int {
            // skip anonymous class and inner function
            if ($node instanceof Class_) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            // skip nested scope
            if ($node instanceof FunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($node instanceof Stmt && !$node instanceof Expression) {
                $yieldNodes = [];
                return NodeVisitor::STOP_TRAVERSAL;
            }
            if (!$node instanceof Yield_ && !$node instanceof YieldFrom) {
                return null;
            }
            $yieldNodes[] = $node;
            return null;
        });
        return $yieldNodes;
    }
}
