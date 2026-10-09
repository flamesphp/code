<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Error;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<Param>
 */
final class ParamNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return Param::class;
    }
    /**
     * @param Param $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->var instanceof Error) {
            return null;
        }
        if ($node->var->name instanceof Expr) {
            return null;
        }
        return $node->var->name;
    }
}
