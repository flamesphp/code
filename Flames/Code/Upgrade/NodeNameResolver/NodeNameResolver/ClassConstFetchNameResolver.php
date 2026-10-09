<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
/**
 * @implements NodeNameResolverInterface<ClassConstFetch>
 */
final class ClassConstFetchNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return ClassConstFetch::class;
    }
    /**
     * @param ClassConstFetch $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->class instanceof Expr) {
            return null;
        }
        if (!$node->name instanceof Identifier) {
            return null;
        }
        $class = $node->class->toString();
        $name = $node->name->toString();
        return $class . '::' . $name;
    }
}
