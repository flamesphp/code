<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\Contract;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use PHPStan\Type\Type;
/**
 * @template TNode as \Flames\Code\Upgrade\ThirdParty\PhpParser\Node
 */
interface NodeTypeResolverInterface
{
    /**
     * @return array<class-string<TNode>>
     */
    public function getNodeClasses(): array;
    /**
     * @param TNode $node
     */
    public function resolve(Node $node): Type;
}
