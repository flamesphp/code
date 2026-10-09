<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ErrorSuppress;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\List_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeNestingScope\ContextAnalyzer;
use Flames\Code\Upgrade\Rules\Php72\ValueObject\ListAndEach;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class AssignManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder, private PropertyFetchAnalyzer $propertyFetchAnalyzer, private ContextAnalyzer $contextAnalyzer)
    {
    }
    /**
     * Matches:
     * list([1, 2]) = each($items)
     */
    public function matchListAndEach(Assign $assign): ?ListAndEach
    {
        // could be behind error suppress
        if ($assign->expr instanceof ErrorSuppress) {
            $errorSuppress = $assign->expr;
            $bareExpr = $errorSuppress->expr;
        } else {
            $bareExpr = $assign->expr;
        }
        if (!$bareExpr instanceof FuncCall) {
            return null;
        }
        if (!$assign->var instanceof List_) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($bareExpr, 'each')) {
            return null;
        }
        // no placeholders
        if ($bareExpr->isFirstClassCallable()) {
            return null;
        }
        return new ListAndEach($assign->var, $bareExpr);
    }
    /**
     * @api doctrine
     * @return array<PropertyFetch|StaticPropertyFetch>
     */
    public function resolveAssignsToLocalPropertyFetches(FunctionLike $functionLike): array
    {
        return $this->betterNodeFinder->find((array) $functionLike->getStmts(), function (Node $node): bool {
            if (!$this->propertyFetchAnalyzer->isLocalPropertyFetch($node)) {
                return \false;
            }
            return $this->contextAnalyzer->isLeftPartOfAssign($node);
        });
    }
}
