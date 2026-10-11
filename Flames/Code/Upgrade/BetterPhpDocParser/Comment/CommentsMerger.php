<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Comment;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\InlineHTML;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class CommentsMerger
{
    public function __construct(private NodeComparator $nodeComparator)
    {
    }
    public function mirrorComments(Node $newNode, Node $oldNode): void
    {
        if ($oldNode instanceof InlineHTML) {
            return;
        }
        if ($this->nodeComparator->areSameNode($newNode, $oldNode)) {
            return;
        }
        $oldPhpDocInfo = $oldNode->getAttribute(AttributeKey::PHP_DOC_INFO);
        $newPhpDocInfo = $newNode->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($newPhpDocInfo instanceof PhpDocInfo) {
            if (!$oldPhpDocInfo instanceof PhpDocInfo) {
                return;
            }
            if ((string) $oldPhpDocInfo->getPhpDocNode() !== (string) $newPhpDocInfo->getPhpDocNode()) {
                return;
            }
        }
        $newNode->setAttribute(AttributeKey::PHP_DOC_INFO, $oldPhpDocInfo);
        if (!$newNode instanceof Nop) {
            $newNode->setAttribute(AttributeKey::COMMENTS, $oldNode->getAttribute(AttributeKey::COMMENTS));
        }
    }
    /**
     * @param Node[] $mergedNodes
     */
    public function keepComments(Node $newNode, array $mergedNodes): void
    {
        $comments = $newNode->getComments();
        foreach ($mergedNodes as $mergedNode) {
            $comments = array_merge($comments, $mergedNode->getComments());
        }
        if ($comments === []) {
            return;
        }
        $newNode->setAttribute(AttributeKey::COMMENTS, $comments);
        // remove so comments "win"
        $newNode->setAttribute(AttributeKey::PHP_DOC_INFO, null);
    }
}
