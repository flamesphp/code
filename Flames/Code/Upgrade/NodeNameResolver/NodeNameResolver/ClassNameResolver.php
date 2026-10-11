<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<ClassLike>
 */
final class ClassNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return ClassLike::class;
    }
    /**
     * @param ClassLike $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->namespacedName instanceof Name) {
            return $node->namespacedName->toString();
        }
        if (!$node->name instanceof Identifier) {
            return null;
        }
        return $node->name->toString();
    }
}
