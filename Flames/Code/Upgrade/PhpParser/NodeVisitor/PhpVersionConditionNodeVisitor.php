<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitorAbstract;
use Flames\Code\Upgrade\Contract\PhpParser\DecoratingNodeVisitorInterface;
use Flames\Code\Upgrade\Rules\DeadCode\ConditionResolver;
use Flames\Code\Upgrade\Rules\DeadCode\ValueObject\VersionCompareCondition;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\NodeTraverser\SimpleNodeTraverser;
final class PhpVersionConditionNodeVisitor extends NodeVisitorAbstract implements DecoratingNodeVisitorInterface
{
    public function __construct(private readonly ConditionResolver $conditionResolver)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (($node instanceof Ternary || $node instanceof If_) && $this->hasVersionCompareCond($node)) {
            if ($node instanceof Ternary) {
                $nodes = [$node->else];
                if ($node->if instanceof Node) {
                    $nodes[] = $node->if;
                }
            } else {
                $nodes = $node->stmts;
            }
            SimpleNodeTraverser::decorateWithAttributeValue($nodes, AttributeKey::PHP_VERSION_CONDITIONED, \true);
        }
        return null;
    }
    /**
     * @param \PhpParser\Node\Stmt\If_|\PhpParser\Node\Expr\Ternary $ifOrTernary
     */
    private function hasVersionCompareCond($ifOrTernary): bool
    {
        if (!$ifOrTernary->cond instanceof FuncCall) {
            return \false;
        }
        $versionCompare = $this->conditionResolver->resolveFromExpr($ifOrTernary->cond);
        return $versionCompare instanceof VersionCompareCondition;
    }
}
