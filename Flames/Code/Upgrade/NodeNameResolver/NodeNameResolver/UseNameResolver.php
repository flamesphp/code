<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<Use_>
 */
final class UseNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return Use_::class;
    }
    /**
     * @param Use_ $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->uses === []) {
            return null;
        }
        $onlyUse = $node->uses[0];
        return $onlyUse->name->toString();
    }
}
