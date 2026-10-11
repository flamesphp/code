<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_string;
use function spl_object_id;
use function sprintf;
/**
 * `IgnoredErrorHelper` may collapse several configured ignores into one
 * merged entry, so `message`/`rawMessage`/`identifier` are nullable here.
 * It also attaches `realPath` once the configured path is resolved. The
 * `messages`/`rawMessages`/`identifiers` keys remain in the inferred shape
 * even after expansion + unset (PHPStan does not strip optional keys via
 * negative isset on sealed shapes), so the type lists them explicitly here
 * — they are never read, only tolerated. `paths` is `array<int<0, max>,
 * string>` rather than `list<string>` because `process()` unsets matched
 * entries by index, breaking list-ness.
 *
 * @phpstan-type ExpandedIgnoredErrorData = array{
 *     message?: string|null,
 *     rawMessage?: string|null,
 *     identifier?: string|null,
 *     messages?: list<string>,
 *     rawMessages?: list<string>,
 *     identifiers?: list<string>,
 *     path?: string,
 *     paths?: array<int<0, max>, string>,
 *     count?: int,
 *     reportUnmatched?: bool,
 *     realPath?: string,
 * }
 */
final class IgnoredErrorHelperResult
{
    private FileHelper $fileHelper;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $errors;
    /**
     * @var array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}>
     */
    private array $otherIgnoreErrors;
    /**
     * @var array<string, array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}>>
     */
    private array $ignoreErrorsByFile;
    /**
     * @var (string|ExpandedIgnoredErrorData)[]
     */
    private array $ignoreErrors;
    private bool $reportUnmatchedIgnoredErrors;
    /**
     * @param list<string> $errors
     * @param array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}> $otherIgnoreErrors
     * @param array<string, array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}>> $ignoreErrorsByFile
     * @param (string|ExpandedIgnoredErrorData)[] $ignoreErrors
     */
    public function __construct(
        FileHelper $fileHelper,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $errors,
        array $otherIgnoreErrors,
        array $ignoreErrorsByFile,
        array $ignoreErrors,
        bool $reportUnmatchedIgnoredErrors
    )
    {
        $this->fileHelper = $fileHelper;
        $this->errors = $errors;
        $this->otherIgnoreErrors = $otherIgnoreErrors;
        $this->ignoreErrorsByFile = $ignoreErrorsByFile;
        $this->ignoreErrors = $ignoreErrors;
        $this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
    }
    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /**
     * @param array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}> $ignoreErrors
     * @return array<array{index: int<0, max>, ignoreError: string|ExpandedIgnoredErrorData}>
     */
    private static function filterIgnoreErrorsByIdentifier(array $ignoreErrors, ?string $identifier): array
    {
        $filtered = [];
        foreach ($ignoreErrors as $ignoreError) {
            $ignore = $ignoreError['ignoreError'];
            if (is_array($ignore) && isset($ignore['identifier']) && $ignore['identifier'] !== $identifier) {
                continue;
            }
            $filtered[] = $ignoreError;
        }
        return $filtered;
    }
    /**
     * @param list<Error> $errors
     * @param string[] $analysedFiles
     */
    public function process(array $errors, bool $onlyFiles, array $analysedFiles, bool $hasInternalErrors): \PHPStan\Analyser\Ignore\IgnoredErrorHelperProcessedResult
    {
        $unmatchedIgnoredErrors = $this->ignoreErrors;
        $stringErrors = [];
        // Per-entry runtime state for `count`-bounded ignores. Tracked in side
        // maps keyed by the same index so `$unmatchedIgnoredErrors` keeps the
        // `(string|ExpandedIgnoredErrorData)[]` shape across the closure's
        // offset writes — otherwise PHPStan widens it to `array<mixed>`.
        $realCounts = [];
        $matchedAt = [];
        // Preserve configuration order while filtering out identifier-specific
        // entries that cannot match, once per distinct error identifier.
        $otherIgnoreErrorsByIdentifier = [];
        $ignoreErrorsByFileAndIdentifier = [];
        $processIgnoreError = function (Error $error, int $i, $ignore) use (&$unmatchedIgnoredErrors, &$stringErrors, &$realCounts, &$matchedAt): bool {
            $shouldBeIgnored = \false;
            if (is_string($ignore)) {
                $shouldBeIgnored = \PHPStan\Analyser\Ignore\IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore, null, null, null);
                if ($shouldBeIgnored) {
                    unset($unmatchedIgnoredErrors[$i]);
                }
            } else if (isset($ignore['path'])) {
                $shouldBeIgnored = \PHPStan\Analyser\Ignore\IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'] ?? null, $ignore['rawMessage'] ?? null, $ignore['identifier'] ?? null, $ignore['path']);
                if ($shouldBeIgnored) {
                    if (isset($ignore['count'])) {
                        $realCount = ($realCounts[$i] ?? 0) + 1;
                        $realCounts[$i] = $realCount;
                        if (!isset($matchedAt[$i])) {
                            $matchedAt[$i] = ['file' => $error->file, 'line' => $error->line];
                        }
                        if ($realCount > $ignore['count']) {
                            $shouldBeIgnored = \false;
                        }
                    } else {
                        unset($unmatchedIgnoredErrors[$i]);
                    }
                }
            } elseif (isset($ignore['paths'])) {
                // Message and identifier do not depend on the path. Match them once
                // instead of repeating the potentially expensive regex for every path.
                $matchesMessageAndIdentifier = \PHPStan\Analyser\Ignore\IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'] ?? null, $ignore['rawMessage'] ?? null, $ignore['identifier'] ?? null, null);
                if ($matchesMessageAndIdentifier) {
                    foreach ($ignore['paths'] as $j => $ignorePath) {
                        $shouldBeIgnored = \PHPStan\Analyser\Ignore\IgnoredError::shouldIgnore($this->fileHelper, $error, null, null, null, $ignorePath);
                        if (!$shouldBeIgnored) {
                            continue;
                        }
                        if (isset($unmatchedIgnoredErrors[$i])) {
                            if (!is_array($unmatchedIgnoredErrors[$i])) {
                                throw new ShouldNotHappenException();
                            }
                            unset($unmatchedIgnoredErrors[$i]['paths'][$j]);
                            if (isset($unmatchedIgnoredErrors[$i]['paths']) && count($unmatchedIgnoredErrors[$i]['paths']) === 0) {
                                unset($unmatchedIgnoredErrors[$i]);
                            }
                        }
                        break;
                    }
                }
            } else {
                $shouldBeIgnored = \PHPStan\Analyser\Ignore\IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'] ?? null, $ignore['rawMessage'] ?? null, $ignore['identifier'] ?? null, null);
                if ($shouldBeIgnored) {
                    unset($unmatchedIgnoredErrors[$i]);
                }
            }
            if ($shouldBeIgnored) {
                if (!($error->canBeIgnored === \true)) {
                    $stringErrors[] = sprintf('Error message "%s" cannot be ignored, use excludePaths instead.', $error->message);
                    return \true;
                }
                return \false;
            }
            return \true;
        };
        $ignoredErrors = [];
        $notIgnoredErrors = [];
        $strippedSurvivors = [];
        $errorQueue = $errors;
        for ($errorIndex = 0; $errorIndex < count($errorQueue); $errorIndex++) {
            $error = $errorQueue[$errorIndex];
            $identifier = $error->identifier;
            $identifierKey = $identifier ?? '';
            $matchingOtherIgnoreErrors = $otherIgnoreErrorsByIdentifier[$identifierKey] ??= self::filterIgnoreErrorsByIdentifier($this->otherIgnoreErrors, $identifier);
            // An error deduplicated directly into a trait (see ConstantConditionInTraitRule)
            // stands for one occurrence per using class. An ignoreErrors path pointing at one
            // of the using classes accounts only for that class's context - it must not hide
            // the occurrences of the remaining contexts, which are re-queued and reported
            // (or further ignored) each in its own context.
            $traitContexts = $error->traitContexts;
            if (count($traitContexts) > 0) {
                $errorTraitFilePath = $error->traitFilePath;
                $remainingContexts = $traitContexts;
                foreach (array_keys($traitContexts) as $contextFilePath) {
                    $contextError = $error->asReportedInTraitContext($contextFilePath);
                    $contextIgnored = \false;
                    $normalizedContextFilePath = $this->fileHelper->normalizePath($contextFilePath);
                    $matchingFileIgnoreErrors = $ignoreErrorsByFileAndIdentifier[$normalizedContextFilePath][$identifierKey] ??= self::filterIgnoreErrorsByIdentifier($this->ignoreErrorsByFile[$normalizedContextFilePath] ?? [], $identifier);
                    foreach ($matchingFileIgnoreErrors as $ignoreError) {
                        $i = $ignoreError['index'];
                        $ignore = $ignoreError['ignoreError'];
                        if (!$processIgnoreError($contextError, $i, $ignore)) {
                            $ignoredErrors[] = [$contextError, $ignore];
                            $contextIgnored = \true;
                            break;
                        }
                    }
                    if (!$contextIgnored) {
                        foreach ($matchingOtherIgnoreErrors as $ignoreError) {
                            $i = $ignoreError['index'];
                            $ignore = $ignoreError['ignoreError'];
                            // only entries scoped to a path that does not cover the trait file
                            // itself act per-context - everything else (pathless entries, paths
                            // covering the trait) matches the deduplicated error as a whole below
                            if (!is_array($ignore) || !isset($ignore['path'])) {
                                continue;
                            }
                            if ($errorTraitFilePath !== null && (new FileExcluder($this->fileHelper, [$ignore['path']]))->isExcludedFromAnalysing($errorTraitFilePath)) {
                                continue;
                            }
                            if (!$processIgnoreError($contextError, $i, $ignore)) {
                                $ignoredErrors[] = [$contextError, $ignore];
                                $contextIgnored = \true;
                                break;
                            }
                        }
                    }
                    if (!$contextIgnored) {
                        continue;
                    }
                    unset($remainingContexts[$contextFilePath]);
                }
                if (count($remainingContexts) < count($traitContexts)) {
                    foreach (array_keys($remainingContexts) as $contextFilePath) {
                        $survivor = $error->asReportedInTraitContext($contextFilePath);
                        // the strip phase above already tried this occurrence against its
                        // file's entries and the context-scoped path entries - it must not
                        // be counted against them a second time below
                        $strippedSurvivors[spl_object_id($survivor)] = \true;
                        $errorQueue[] = $survivor;
                    }
                    continue;
                }
                // no class-scoped entry matched any context - the error stays deduplicated
                // in the trait and is matched as a whole below
            }
            $isStrippedSurvivor = isset($strippedSurvivors[spl_object_id($error)]);
            $filePath = $this->fileHelper->normalizePath($error->getFilePath());
            if (!$isStrippedSurvivor && isset($this->ignoreErrorsByFile[$filePath])) {
                $matchingFileIgnoreErrors = $ignoreErrorsByFileAndIdentifier[$filePath][$identifierKey] ??= self::filterIgnoreErrorsByIdentifier($this->ignoreErrorsByFile[$filePath], $identifier);
                foreach ($matchingFileIgnoreErrors as $ignoreError) {
                    $i = $ignoreError['index'];
                    $ignore = $ignoreError['ignoreError'];
                    $result = $processIgnoreError($error, $i, $ignore);
                    if (!$result) {
                        $ignoredErrors[] = [$error, $ignore];
                        continue 2;
                    }
                }
            }
            $traitFilePath = $error->traitFilePath;
            if ($traitFilePath !== null) {
                $normalizedTraitFilePath = $this->fileHelper->normalizePath($traitFilePath);
                if (isset($this->ignoreErrorsByFile[$normalizedTraitFilePath])) {
                    $matchingFileIgnoreErrors = $ignoreErrorsByFileAndIdentifier[$normalizedTraitFilePath][$identifierKey] ??= self::filterIgnoreErrorsByIdentifier($this->ignoreErrorsByFile[$normalizedTraitFilePath], $identifier);
                    foreach ($matchingFileIgnoreErrors as $ignoreError) {
                        $i = $ignoreError['index'];
                        $ignore = $ignoreError['ignoreError'];
                        $result = $processIgnoreError($error, $i, $ignore);
                        if (!$result) {
                            $ignoredErrors[] = [$error, $ignore];
                            continue 2;
                        }
                    }
                }
            }
            foreach ($matchingOtherIgnoreErrors as $ignoreError) {
                $i = $ignoreError['index'];
                $ignore = $ignoreError['ignoreError'];
                if ($isStrippedSurvivor && is_array($ignore) && isset($ignore['path']) && $error->traitFilePath !== null && !(new FileExcluder($this->fileHelper, [$ignore['path']]))->isExcludedFromAnalysing($error->traitFilePath)) {
                    // the strip phase already counted this occurrence against
                    // context-scoped path entries
                    continue;
                }
                $result = $processIgnoreError($error, $i, $ignore);
                if (!$result) {
                    $ignoredErrors[] = [$error, $ignore];
                    continue 2;
                }
            }
            $notIgnoredErrors[] = $error;
        }
        $errors = $notIgnoredErrors;
        foreach ($unmatchedIgnoredErrors as $i => $unmatchedIgnoredError) {
            if (!is_array($unmatchedIgnoredError) || !isset($unmatchedIgnoredError['count']) || !isset($realCounts[$i])) {
                continue;
            }
            $realCount = $realCounts[$i];
            if ($realCount <= $unmatchedIgnoredError['count']) {
                continue;
            }
            $matchedFile = $matchedAt[$i]['file'] ?? null;
            $matchedLine = $matchedAt[$i]['line'] ?? null;
            $errors[] = (new Error(sprintf('%s %s is expected to occur %d %s, but occurred %d %s.', \PHPStan\Analyser\Ignore\IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError), \PHPStan\Analyser\Ignore\IgnoredError::stringifyPattern($unmatchedIgnoredError), $unmatchedIgnoredError['count'], $unmatchedIgnoredError['count'] === 1 ? 'time' : 'times', $realCount, $realCount === 1 ? 'time' : 'times'), $matchedFile ?? '', $matchedLine, \false))->withIdentifier('ignore.count');
        }
        $analysedFilesKeys = array_fill_keys($analysedFiles, \true);
        if (!$hasInternalErrors) {
            foreach ($unmatchedIgnoredErrors as $i => $unmatchedIgnoredError) {
                $reportUnmatched = is_array($unmatchedIgnoredError) ? $unmatchedIgnoredError['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors : $this->reportUnmatchedIgnoredErrors;
                if ($reportUnmatched === \false) {
                    continue;
                }
                $realCount = $realCounts[$i] ?? null;
                if (isset($unmatchedIgnoredError['count']) && $realCount !== null && (isset($unmatchedIgnoredError['realPath']) || !$onlyFiles)) {
                    if ($realCount < $unmatchedIgnoredError['count']) {
                        $matchedFile = $matchedAt[$i]['file'] ?? null;
                        $matchedLine = $matchedAt[$i]['line'] ?? null;
                        // $realCount is at least 1 (it was incremented in the closure)
                        // and strictly less than count, so count is always >= 2.
                        $errors[] = (new Error(sprintf('%s %s is expected to occur %d times, but occurred only %d %s.', \PHPStan\Analyser\Ignore\IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError), \PHPStan\Analyser\Ignore\IgnoredError::stringifyPattern($unmatchedIgnoredError), $unmatchedIgnoredError['count'], $realCount, $realCount === 1 ? 'time' : 'times'), $matchedFile ?? '', $matchedLine, \false))->withIdentifier('ignore.count');
                    }
                } elseif (isset($unmatchedIgnoredError['realPath'])) {
                    if (!array_key_exists($unmatchedIgnoredError['realPath'], $analysedFilesKeys)) {
                        continue;
                    }
                    if ($onlyFiles) {
                        continue;
                    }
                    $errors[] = (new Error(sprintf('%s %s was not matched in reported errors.', \PHPStan\Analyser\Ignore\IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError), \PHPStan\Analyser\Ignore\IgnoredError::stringifyPattern($unmatchedIgnoredError)), $unmatchedIgnoredError['realPath'], \null, \false))->withIdentifier('ignore.unmatched');
                } elseif (!$onlyFiles) {
                    $stringErrors[] = sprintf('%s %s was not matched in reported errors.', \PHPStan\Analyser\Ignore\IgnoredError::getIgnoredErrorLabel($unmatchedIgnoredError), \PHPStan\Analyser\Ignore\IgnoredError::stringifyPattern($unmatchedIgnoredError));
                }
            }
        }
        return new \PHPStan\Analyser\Ignore\IgnoredErrorHelperProcessedResult($errors, $ignoredErrors, $stringErrors);
    }
}
