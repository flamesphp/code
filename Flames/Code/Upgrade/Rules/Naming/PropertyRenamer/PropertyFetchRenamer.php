<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\PropertyRenamer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VarLikeIdentifier;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
final readonly class PropertyFetchRenamer
{
    public function __construct(private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private PropertyFetchAnalyzer $propertyFetchAnalyzer)
    {
    }
    public function renamePropertyFetchesInClass(ClassLike $classLike, string $currentName, string $expectedName): void
    {
        // 1. replace property fetch rename in whole class
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($classLike, function (Node $node) use ($currentName, $expectedName): ?Node {
            if (!$this->propertyFetchAnalyzer->isLocalPropertyFetchName($node, $currentName)) {
                return null;
            }
            /** @var StaticPropertyFetch|PropertyFetch $node */
            $node->name = $node instanceof PropertyFetch ? new Identifier($expectedName) : new VarLikeIdentifier($expectedName);
            return $node;
        });
    }
}
