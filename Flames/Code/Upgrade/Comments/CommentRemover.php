<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Comments;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\Comments\NodeTraverser\CommentRemovingNodeTraverser;
/**
 * @see \Flames\Code\Upgrade\Tests\Comments\CommentRemover\CommentRemoverTest
 */
final readonly class CommentRemover
{
    public function __construct(private CommentRemovingNodeTraverser $commentRemovingNodeTraverser)
    {
    }
    /**
     * @param Node[]|Node|null $node
     * @return Node[]|null
     */
    public function removeFromNode($node): ?array
    {
        if ($node === null) {
            return null;
        }
        $nodes = is_array($node) ? $node : [$node];
        return $this->commentRemovingNodeTraverser->traverse($nodes);
    }
}
