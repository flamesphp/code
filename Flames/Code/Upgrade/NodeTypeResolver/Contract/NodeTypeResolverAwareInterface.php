<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\Contract;

use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
interface NodeTypeResolverAwareInterface
{
    public function autowire(NodeTypeResolver $nodeTypeResolver): void;
}
