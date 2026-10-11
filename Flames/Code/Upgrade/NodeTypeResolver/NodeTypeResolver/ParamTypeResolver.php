<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverAwareInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\PerNodeTypeResolver\ParamTypeResolver\ParamTypeResolverTest
 *
 * @implements NodeTypeResolverInterface<Param>
 */
final class ParamTypeResolver implements NodeTypeResolverInterface, NodeTypeResolverAwareInterface
{
    private NodeTypeResolver $nodeTypeResolver;
    public function autowire(NodeTypeResolver $nodeTypeResolver): void
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeClasses(): array
    {
        return [Param::class];
    }
    /**
     * @param Param $node
     */
    public function resolve(Node $node): Type
    {
        if ($node->type === null) {
            return new MixedType();
        }
        return $this->nodeTypeResolver->getType($node->type);
    }
}
