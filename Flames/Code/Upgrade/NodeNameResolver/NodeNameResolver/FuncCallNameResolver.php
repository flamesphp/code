<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\NodeNameResolver\Contract\NodeNameResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
/**
 * @implements NodeNameResolverInterface<FuncCall>
 */
final readonly class FuncCallNameResolver implements NodeNameResolverInterface
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }
    public function getNode(): string
    {
        return FuncCall::class;
    }
    /**
     * If some function is namespaced, it will be used over global one.
     * But only if it really exists.
     *
     * @param FuncCall $node
     */
    public function resolve(Node $node, ?Scope $scope): ?string
    {
        if ($node->name instanceof Expr) {
            return null;
        }
        $namespaceName = $node->name->getAttribute(AttributeKey::NAMESPACED_NAME);
        if ($namespaceName instanceof FullyQualified) {
            $functionFqnName = $namespaceName->toString();
            if ($this->reflectionProvider->hasFunction($namespaceName, null)) {
                return $functionFqnName;
            }
        }
        if (is_string($namespaceName)) {
            return $namespaceName;
        }
        return (string) $node->name;
    }
}
