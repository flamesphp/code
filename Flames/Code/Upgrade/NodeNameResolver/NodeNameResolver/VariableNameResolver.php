<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<Variable>
 */
final class VariableNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return Variable::class;
    }
    /**
     * @param Variable $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->name instanceof Expr) {
            return null;
        }
        return $node->name;
    }
}
