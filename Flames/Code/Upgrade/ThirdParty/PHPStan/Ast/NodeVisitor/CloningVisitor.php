<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\AbstractNodeVisitor;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Attribute;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
final class CloningVisitor extends AbstractNodeVisitor
{
    public function enterNode(Node $originalNode): Node
    {
        $node = clone $originalNode;
        $node->setAttribute(Attribute::ORIGINAL_NODE, $originalNode);
        return $node;
    }
}
