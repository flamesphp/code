<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Scope;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use PHPStan\Analyser\MutatingScope;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
/**
 * Handle Scope filling when there is error \PHPStan\Parser\ParserErrorsException
 * from PHPStan NodeScopeResolver
 */
final class RectorNodeScopeResolver
{
    /**
     * @param Stmt[] $stmts
     */
    public static function processNodes(array $stmts, MutatingScope $mutatingScope): void
    {
        $simpleCallableNodeTraverser = new SimpleCallableNodeTraverser();
        $simpleCallableNodeTraverser->traverseNodesWithCallable($stmts, function (Node $node) use ($mutatingScope) {
            $node->setAttribute(AttributeKey::SCOPE, $mutatingScope);
            return null;
        });
    }
}
