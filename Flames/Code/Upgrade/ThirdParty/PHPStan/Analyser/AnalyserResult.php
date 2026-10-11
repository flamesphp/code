<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
use function usort;
/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type CollectorData from CollectedData
 * @phpstan-import-type ValueDependencies from ValueDependencyCollector
 */
final class AnalyserResult
{
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $unorderedErrors;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $filteredPhpErrors;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $allPhpErrors;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $locallyIgnoredErrors;
    /**
     * @var array<string, LinesToIgnore>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $linesToIgnore;
    /**
     * @var array<string, LinesToIgnore>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $unmatchedLineIgnores;
    /**
     * @var list<InternalError>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $internalErrors;
    /**
     * @var CollectorData
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $collectedData;
    /**
     * @var array<string, array<string>>|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?array $dependencies;
    /**
     * @var array<string, array<string>>|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?array $usedTraitDependencies;
    /**
     * @var ValueDependencies|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?array $valueDependencies;
    /**
     * @var array<string, array<string>>|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?array $packageDependencies;
    /**
     * @var array<string, array<RootExportedNode>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $exportedNodes;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $reachedInternalErrorsCountLimit;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $peakMemoryUsageBytes;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $processedFiles;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $workerCount;
    /** @var list<Error>|null */
    private ?array $errors = null;
    /**
     * @param list<Error> $unorderedErrors
     * @param list<Error> $filteredPhpErrors
     * @param list<Error> $allPhpErrors
     * @param list<Error> $locallyIgnoredErrors
     * @param array<string, LinesToIgnore> $linesToIgnore
     * @param array<string, LinesToIgnore> $unmatchedLineIgnores
     * @param CollectorData $collectedData
     * @param list<InternalError> $internalErrors
     * @param array<string, array<string>>|null $dependencies
     * @param array<string, array<string>>|null $usedTraitDependencies
     * @param ValueDependencies|null $valueDependencies see ValueDependencyCollector
     * @param array<string, array<string>>|null $packageDependencies
     * @param array<string, array<RootExportedNode>> $exportedNodes
     * @param list<string> $processedFiles
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unorderedErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $filteredPhpErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $allPhpErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $locallyIgnoredErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $linesToIgnore,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unmatchedLineIgnores,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $internalErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $collectedData,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?array $dependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?array $usedTraitDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?array $valueDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?array $packageDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $exportedNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $reachedInternalErrorsCountLimit,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $peakMemoryUsageBytes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $processedFiles,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $workerCount = 0
    )
    {
        $this->unorderedErrors = $unorderedErrors;
        $this->filteredPhpErrors = $filteredPhpErrors;
        $this->allPhpErrors = $allPhpErrors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
        $this->internalErrors = $internalErrors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->usedTraitDependencies = $usedTraitDependencies;
        $this->valueDependencies = $valueDependencies;
        $this->packageDependencies = $packageDependencies;
        $this->exportedNodes = $exportedNodes;
        $this->reachedInternalErrorsCountLimit = $reachedInternalErrorsCountLimit;
        $this->peakMemoryUsageBytes = $peakMemoryUsageBytes;
        $this->processedFiles = $processedFiles;
        $this->workerCount = $workerCount;
    }
    /**
     * How many parallel workers produced this result; 0 when the analysis ran in
     * the main process.
     */
    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }
    /**
     * @return list<Error>
     */
    public function getUnorderedErrors(): array
    {
        return $this->unorderedErrors;
    }
    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        if (!isset($this->errors)) {
            $this->errors = $this->unorderedErrors;
            usort($this->errors, static fn(\PHPStan\Analyser\Error $a, \PHPStan\Analyser\Error $b): int => [$a->file, $a->line, $a->message] <=> [$b->file, $b->line, $b->message]);
        }
        return $this->errors;
    }
    /**
     * @return list<Error>
     */
    public function getFilteredPhpErrors(): array
    {
        return $this->filteredPhpErrors;
    }
    /**
     * @return list<Error>
     */
    public function getAllPhpErrors(): array
    {
        return $this->allPhpErrors;
    }
    /**
     * @return list<Error>
     */
    public function getLocallyIgnoredErrors(): array
    {
        return $this->locallyIgnoredErrors;
    }
    /**
     * @return array<string, LinesToIgnore>
     */
    public function getLinesToIgnore(): array
    {
        return $this->linesToIgnore;
    }
    /**
     * @return array<string, LinesToIgnore>
     */
    public function getUnmatchedLineIgnores(): array
    {
        return $this->unmatchedLineIgnores;
    }
    /**
     * @return list<InternalError>
     */
    public function getInternalErrors(): array
    {
        return $this->internalErrors;
    }
    /**
     * @return CollectorData
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    /**
     * @return array<string, array<string>>|null
     */
    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }
    /**
     * @return array<string, array<string>>|null
     */
    public function getUsedTraitDependencies(): ?array
    {
        return $this->usedTraitDependencies;
    }
    /**
     * @return ValueDependencies|null
     */
    public function getValueDependencies(): ?array
    {
        return $this->valueDependencies;
    }
    /**
     * @return array<string, array<string>>|null
     */
    public function getPackageDependencies(): ?array
    {
        return $this->packageDependencies;
    }
    /**
     * @return array<string, array<RootExportedNode>>
     */
    public function getExportedNodes(): array
    {
        return $this->exportedNodes;
    }
    public function hasReachedInternalErrorsCountLimit(): bool
    {
        return $this->reachedInternalErrorsCountLimit;
    }
    public function getPeakMemoryUsageBytes(): int
    {
        return $this->peakMemoryUsageBytes;
    }
    /**
     * @return list<string>
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }
}
