<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Comments\NodeTraverser;

use PhpParser\NodeTraverser;
use Flames\Code\Upgrade\Comments\NodeVisitor\CommentRemovingNodeVisitor;
final class CommentRemovingNodeTraverser extends NodeTraverser
{
    public function __construct(CommentRemovingNodeVisitor $commentRemovingNodeVisitor)
    {
        parent::__construct($commentRemovingNodeVisitor);
    }
}
