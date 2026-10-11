<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\NodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitorAbstract;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class PhpDocInfoRemovingNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): Node
    {
        $node->setAttribute(AttributeKey::PHP_DOC_INFO, null);
        return $node;
    }
}
