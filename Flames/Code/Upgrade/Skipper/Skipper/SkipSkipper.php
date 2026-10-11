<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\Skipper;

use Flames\Code\Upgrade\Skipper\Matcher\FileInfoMatcher;
use Flames\Code\Upgrade\Skipper\ValueObject\SkipMatch;
final readonly class SkipSkipper
{
    public function __construct(private FileInfoMatcher $fileInfoMatcher)
    {
    }
    /**
     * @param array<string, string[]|null> $skippedClasses
     * @param object|string $checker
     */
    public function match($checker, string $filePath, array $skippedClasses): ?SkipMatch
    {
        foreach ($skippedClasses as $skippedClass => $skippedFiles) {
            if (!is_a($checker, $skippedClass, \true)) {
                continue;
            }
            // skip everywhere
            if (!is_array($skippedFiles)) {
                return new SkipMatch($skippedClass, null);
            }
            // the same path can be skipped under multiple rules, so the matched path is reported
            // scoped to its rule, not tracked on its own
            $matchedPath = $this->fileInfoMatcher->matchPattern($filePath, $skippedFiles);
            if ($matchedPath !== null) {
                return new SkipMatch($skippedClass, $matchedPath);
            }
        }
        return null;
    }
}
