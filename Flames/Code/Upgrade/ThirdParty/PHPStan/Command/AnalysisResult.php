<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\InternalError;
use PHPStan\Collectors\CollectedData;
use function count;
use function usort;
/**
 * @api
 */
final class AnalysisResult
{
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $notFileSpecificErrors;
    /**
     * @var list<InternalError>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $internalErrors;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $warnings;
    /**
     * @var list<CollectedData>
     */
    private array $collectedData;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $defaultLevelUsed;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $projectConfigFile;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $savedResultCache;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $peakMemoryUsageBytes;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isResultCacheUsed;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $processedFiles;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $resultCacheExisted;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $workerCount;
    /** @var list<Error> sorted by their file name, line number and message */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $fileSpecificErrors;
    /**
     * @param list<Error> $fileSpecificErrors
     * @param list<string> $notFileSpecificErrors
     * @param list<InternalError> $internalErrors
     * @param list<string> $warnings
     * @param list<CollectedData> $collectedData
     * @param list<string> $processedFiles
     */
    public function __construct(
        array $fileSpecificErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $notFileSpecificErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $internalErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $warnings,
        array $collectedData,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $defaultLevelUsed,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $projectConfigFile,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $savedResultCache,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $peakMemoryUsageBytes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isResultCacheUsed,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $processedFiles = [],
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $resultCacheExisted = \true,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $workerCount = 0
    )
    {
        $this->notFileSpecificErrors = $notFileSpecificErrors;
        $this->internalErrors = $internalErrors;
        $this->warnings = $warnings;
        $this->collectedData = $collectedData;
        $this->defaultLevelUsed = $defaultLevelUsed;
        $this->projectConfigFile = $projectConfigFile;
        $this->savedResultCache = $savedResultCache;
        $this->peakMemoryUsageBytes = $peakMemoryUsageBytes;
        $this->isResultCacheUsed = $isResultCacheUsed;
        $this->processedFiles = $processedFiles;
        $this->resultCacheExisted = $resultCacheExisted;
        $this->workerCount = $workerCount;
        usort($fileSpecificErrors, static fn(Error $a, Error $b): int => [$a->file, $a->line, $a->message] <=> [$b->file, $b->line, $b->message]);
        $this->fileSpecificErrors = $fileSpecificErrors;
    }
    public function hasErrors(): bool
    {
        return \count($this->fileSpecificErrors) + \count($this->notFileSpecificErrors) > 0;
    }
    public function getTotalErrorsCount(): int
    {
        return count($this->fileSpecificErrors) + count($this->notFileSpecificErrors);
    }
    /**
     * @return list<Error> sorted by their file name, line number and message
     */
    public function getFileSpecificErrors(): array
    {
        return $this->fileSpecificErrors;
    }
    /**
     * @return list<string>
     */
    public function getNotFileSpecificErrors(): array
    {
        return $this->notFileSpecificErrors;
    }
    /**
     * @return list<InternalError>
     */
    public function getInternalErrorObjects(): array
    {
        return $this->internalErrors;
    }
    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }
    /**
     * @return list<CollectedData>
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    public function isDefaultLevelUsed(): bool
    {
        return $this->defaultLevelUsed;
    }
    public function getProjectConfigFile(): ?string
    {
        return $this->projectConfigFile;
    }
    public function hasInternalErrors(): bool
    {
        return count($this->internalErrors) > 0;
    }
    public function isResultCacheSaved(): bool
    {
        return $this->savedResultCache;
    }
    public function getPeakMemoryUsageBytes(): int
    {
        return $this->peakMemoryUsageBytes;
    }
    /**
     * How many parallel workers produced this result; 0 when the analysis ran in
     * the main process.
     */
    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }
    public function isResultCacheUsed(): bool
    {
        return $this->isResultCacheUsed;
    }
    /**
     * Whether the result cache file existed when PHPStan started.
     * False means the cache was never created; a stale/invalid cache that
     * triggered a full re-analysis still counts as existing.
     */
    public function resultCacheExisted(): bool
    {
        return $this->resultCacheExisted;
    }
    /**
     * @deprecated
     * @return array<string, string>
     */
    public function getChangedProjectExtensionFilesOutsideOfAnalysedPaths(): array
    {
        return [];
    }
    /**
     * @return list<string>
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }
    /**
     * @api
     * @param list<Error> $fileSpecificErrors
     */
    public function withFileSpecificErrors(array $fileSpecificErrors): self
    {
        return new self($fileSpecificErrors, $this->notFileSpecificErrors, $this->internalErrors, $this->warnings, $this->collectedData, $this->defaultLevelUsed, $this->projectConfigFile, $this->savedResultCache, $this->peakMemoryUsageBytes, $this->isResultCacheUsed, $this->processedFiles, $this->resultCacheExisted);
    }
}
