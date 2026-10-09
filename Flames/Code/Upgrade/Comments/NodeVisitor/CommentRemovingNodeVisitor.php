<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Comments\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class CommentRemovingNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): Node
    {
        // the node must be cloned, so original node is not touched in final print
        $clonedNode = clone $node;
        $clonedNode->setAttribute(AttributeKey::COMMENTS, []);
        $clonedNode->setAttribute(AttributeKey::PHP_DOC_INFO, null);
        return $clonedNode;
    }
}
