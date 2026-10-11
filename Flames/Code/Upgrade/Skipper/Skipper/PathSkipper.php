<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\Skipper;

use Flames\Code\Upgrade\Skipper\Matcher\FileInfoMatcher;
use Flames\Code\Upgrade\Skipper\SkipCriteriaResolver\SkippedPathsResolver;
final readonly class PathSkipper
{
    public function __construct(private FileInfoMatcher $fileInfoMatcher, private SkippedPathsResolver $skippedPathsResolver, private \Flames\Code\Upgrade\Skipper\Skipper\UsedSkipCollector $usedSkipCollector)
    {
    }
    public function shouldSkip(string $filePath): bool
    {
        $matchedPath = $this->fileInfoMatcher->matchPattern($filePath, $this->skippedPathsResolver->resolve());
        if ($matchedPath === null) {
            return \false;
        }
        $this->usedSkipCollector->markUsed($matchedPath);
        return \true;
    }
}
