<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
use function array_values;
use function count;
use function is_array;
/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
#[AutowiredService]
final class LocalIgnoresProcessor
{
    /**
     * @param list<Error> $temporaryFileErrors
     * @param LinesToIgnore $linesToIgnore
     * @param LinesToIgnore $unmatchedLineIgnores
     */
    public function process(array $temporaryFileErrors, array $linesToIgnore, array $unmatchedLineIgnores): \PHPStan\Analyser\LocalIgnoresProcessorResult
    {
        $fileErrors = [];
        $locallyIgnoredErrors = [];
        foreach ($temporaryFileErrors as $tmpFileError) {
            $line = $tmpFileError->line;
            if ($line !== null && $tmpFileError->canBeIgnored === \true && array_key_exists($tmpFileError->file, $linesToIgnore) && array_key_exists($line, $linesToIgnore[$tmpFileError->file])) {
                $identifiers = $linesToIgnore[$tmpFileError->file][$line];
                if ($identifiers === null) {
                    $locallyIgnoredErrors[] = $tmpFileError;
                    unset($unmatchedLineIgnores[$tmpFileError->file][$line]);
                    continue;
                }
                if ($tmpFileError->identifier === null) {
                    $fileErrors[] = $tmpFileError;
                    continue;
                }
                foreach ($identifiers as $i => $ignoredIdentifier) {
                    if ($ignoredIdentifier['name'] !== $tmpFileError->identifier) {
                        continue;
                    }
                    unset($identifiers[$i]);
                    if (count($identifiers) > 0) {
                        $linesToIgnore[$tmpFileError->file][$line] = array_values($identifiers);
                    } else {
                        unset($linesToIgnore[$tmpFileError->file][$line]);
                    }
                    if (array_key_exists($tmpFileError->file, $unmatchedLineIgnores) && array_key_exists($line, $unmatchedLineIgnores[$tmpFileError->file])) {
                        $unmatchedIgnoredIdentifiers = $unmatchedLineIgnores[$tmpFileError->file][$line];
                        if (is_array($unmatchedIgnoredIdentifiers)) {
                            foreach ($unmatchedIgnoredIdentifiers as $j => $unmatchedIgnoredIdentifier) {
                                if ($ignoredIdentifier['name'] !== $unmatchedIgnoredIdentifier['name']) {
                                    continue;
                                }
                                unset($unmatchedIgnoredIdentifiers[$j]);
                                if (count($unmatchedIgnoredIdentifiers) > 0) {
                                    $unmatchedLineIgnores[$tmpFileError->file][$line] = array_values($unmatchedIgnoredIdentifiers);
                                } else {
                                    unset($unmatchedLineIgnores[$tmpFileError->file][$line]);
                                }
                                break;
                            }
                        }
                    }
                    $locallyIgnoredErrors[] = $tmpFileError;
                    continue 2;
                }
            }
            $fileErrors[] = $tmpFileError;
        }
        return new \PHPStan\Analyser\LocalIgnoresProcessorResult($fileErrors, $locallyIgnoredErrors, $linesToIgnore, $unmatchedLineIgnores);
    }
}
