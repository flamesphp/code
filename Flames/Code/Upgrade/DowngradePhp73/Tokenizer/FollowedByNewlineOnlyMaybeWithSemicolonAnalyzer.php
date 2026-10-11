<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp73\Tokenizer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ValueObject\Application\File;
final class FollowedByNewlineOnlyMaybeWithSemicolonAnalyzer
{
    public function isFollowed(File $file, Node $node): bool
    {
        $oldTokens = $file->getOldTokens();
        $nextTokenPosition = $node->getEndTokenPos() + 1;
        if (isset($oldTokens[$nextTokenPosition]) && (string) $oldTokens[$nextTokenPosition] === ';') {
            ++$nextTokenPosition;
        }
        return !isset($oldTokens[$nextTokenPosition]) || isset($oldTokens[$nextTokenPosition]) && str_starts_with((string) $oldTokens[$nextTokenPosition], "\n");
    }
}
