<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Comments;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class CommentResolver
{
    /**
     * @param int|float $rangeLine
     * @return int|float
     */
    public function resolveRangeLineFromComment($rangeLine, int $endLine, Stmt $nextStmt)
    {
        /** @var Comment[]|null $comments */
        $comments = $nextStmt->getAttribute(AttributeKey::COMMENTS);
        if ($this->hasNoComment($comments)) {
            return $rangeLine;
        }
        /** @var Comment[] $comments */
        $firstComment = $comments[0];
        $line = $firstComment->getStartLine();
        return $line - $endLine;
    }
    /**
     * @param Comment[]|null $comments
     */
    private function hasNoComment(?array $comments): bool
    {
        return $comments === null || $comments === [];
    }
}
