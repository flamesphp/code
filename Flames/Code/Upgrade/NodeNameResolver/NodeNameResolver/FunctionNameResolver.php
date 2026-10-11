<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<Function_>
 */
final class FunctionNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return Function_::class;
    }
    /**
     * @param Function_ $node
     */
    public function resolve(Node $node, ?Scope $scope): string
    {
        $bareName = (string) $node->name;
        if (!$scope instanceof Scope) {
            return $bareName;
        }
        $namespaceName = $scope->getNamespace();
        if ($namespaceName !== null) {
            return $namespaceName . '\\' . $bareName;
        }
        return $bareName;
    }
}
