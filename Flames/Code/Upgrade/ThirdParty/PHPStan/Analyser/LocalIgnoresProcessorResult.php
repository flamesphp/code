<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
final class LocalIgnoresProcessorResult
{
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $fileErrors;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $locallyIgnoredErrors;
    /**
     * @var LinesToIgnore
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $linesToIgnore;
    /**
     * @var LinesToIgnore
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $unmatchedLineIgnores;
    /**
     * @param list<Error> $fileErrors
     * @param list<Error> $locallyIgnoredErrors
     * @param LinesToIgnore $linesToIgnore
     * @param LinesToIgnore $unmatchedLineIgnores
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $fileErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $locallyIgnoredErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $linesToIgnore,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unmatchedLineIgnores
    )
    {
        $this->fileErrors = $fileErrors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
    }
    /**
     * @return list<Error>
     */
    public function getFileErrors(): array
    {
        return $this->fileErrors;
    }
    /**
     * @return list<Error>
     */
    public function getLocallyIgnoredErrors(): array
    {
        return $this->locallyIgnoredErrors;
    }
    /**
     * @return LinesToIgnore
     */
    public function getLinesToIgnore(): array
    {
        return $this->linesToIgnore;
    }
    /**
     * @return LinesToIgnore
     */
    public function getUnmatchedLineIgnores(): array
    {
        return $this->unmatchedLineIgnores;
    }
}
