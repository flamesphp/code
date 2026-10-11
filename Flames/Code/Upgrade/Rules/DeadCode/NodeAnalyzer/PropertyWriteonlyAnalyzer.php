<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafePropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class PropertyWriteonlyAnalyzer
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function hasClassDynamicPropertyNames(Class_ $class): bool
    {
        return (bool) $this->betterNodeFinder->findFirst($class, function (Node $node): bool {
            if ($node instanceof FuncCall && $this->nodeNameResolver->isName($node, 'get_object_vars') && !$node->isFirstClassCallable()) {
                $firstArg = $node->getArgs()[0] ?? null;
                if ($firstArg instanceof Arg && $firstArg->value instanceof Variable && $firstArg->value->name === 'this') {
                    return \true;
                }
            }
            if (!$node instanceof PropertyFetch && !$node instanceof NullsafePropertyFetch) {
                return \false;
            }
            // has dynamic name - could be anything
            return $node->name instanceof Expr;
        });
    }
    /**
     * The property fetches are always only assigned to, nothing else
     *
     * @param array<PropertyFetch|StaticPropertyFetch|NullsafePropertyFetch> $propertyFetches
     */
    public function arePropertyFetchesExclusivelyBeingAssignedTo(array $propertyFetches): bool
    {
        foreach ($propertyFetches as $propertyFetch) {
            if ((bool) $propertyFetch->getAttribute(AttributeKey::IS_MULTI_ASSIGN, \false)) {
                return \false;
            }
            if ((bool) $propertyFetch->getAttribute(AttributeKey::IS_ASSIGNED_TO, \false)) {
                return \false;
            }
            if ((bool) $propertyFetch->getAttribute(AttributeKey::IS_BEING_ASSIGNED, \false)) {
                continue;
            }
            return \false;
        }
        return \true;
    }
}
