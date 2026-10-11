<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type CollectorData from CollectedData
 */
final class ResultCache
{
    /**
     * @var string[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $filesToAnalyse;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $fullAnalysis;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $fullAnalysisReason;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $lastFullAnalysisTime;
    /**
     * @var mixed[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $meta;
    /**
     * @var array<string, list<Error>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $errors;
    /**
     * @var array<string, list<Error>>
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
     * @var CollectorData
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $collectedData;
    /**
     * @var array<string, array<string>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $dependencies;
    /**
     * @var array<string, array<string>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $usedTraitDependencies;
    /**
     * @var array<string, array{string, string, string, list<string>, list<string>}>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $valueDependencies;
    /**
     * @var array<string, array<string>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $packageDependencies;
    /**
     * @var array<string, array<RootExportedNode>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $exportedNodes;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\Analyser\ResultCache\CachedExportedNodes $cachedExportedNodes;
    /**
     * @var array<string, array{string, bool, string}>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $projectExtensionFiles;
    /**
     * @var array<string, string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $currentFileHashes;
    /**
     * @param string[] $filesToAnalyse
     * @param mixed[] $meta
     * @param array<string, list<Error>> $errors
     * @param array<string, list<Error>> $locallyIgnoredErrors
     * @param array<string, LinesToIgnore> $linesToIgnore
     * @param array<string, LinesToIgnore> $unmatchedLineIgnores
     * @param CollectorData $collectedData
     * @param array<string, array<string>> $dependencies
     * @param array<string, array<string>> $usedTraitDependencies
     * @param array<string, array{string, string, string, list<string>, list<string>}> $valueDependencies id => [extension class, key, current value, analysis dependents, declarations dependents] - see ValueDependencyCollector
     * @param array<string, array<string>> $packageDependencies
     * @param array<string, array<RootExportedNode>> $exportedNodes the decoded ones - see $cachedExportedNodes for the rest
     * @param array<string, array{string, bool, string}> $projectExtensionFiles
     * @param array<string, string> $currentFileHashes
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $filesToAnalyse,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $fullAnalysis,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $fullAnalysisReason,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $lastFullAnalysisTime,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $meta,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $errors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $locallyIgnoredErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $linesToIgnore,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unmatchedLineIgnores,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $collectedData,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $dependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $usedTraitDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $valueDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $packageDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $exportedNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\Analyser\ResultCache\CachedExportedNodes $cachedExportedNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $projectExtensionFiles,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $currentFileHashes
    )
    {
        $this->filesToAnalyse = $filesToAnalyse;
        $this->fullAnalysis = $fullAnalysis;
        $this->fullAnalysisReason = $fullAnalysisReason;
        $this->lastFullAnalysisTime = $lastFullAnalysisTime;
        $this->meta = $meta;
        $this->errors = $errors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->usedTraitDependencies = $usedTraitDependencies;
        $this->valueDependencies = $valueDependencies;
        $this->packageDependencies = $packageDependencies;
        $this->exportedNodes = $exportedNodes;
        $this->cachedExportedNodes = $cachedExportedNodes;
        $this->projectExtensionFiles = $projectExtensionFiles;
        $this->currentFileHashes = $currentFileHashes;
    }
    /**
     * @return string[]
     */
    public function getFilesToAnalyse(): array
    {
        return $this->filesToAnalyse;
    }
    public function isFullAnalysis(): bool
    {
        return $this->fullAnalysis;
    }
    /**
     * Human-readable explanation of why the cached results could not be reused,
     * null when the result cache is being used.
     */
    public function getFullAnalysisReason(): ?string
    {
        return $this->fullAnalysisReason;
    }
    public function getLastFullAnalysisTime(): int
    {
        return $this->lastFullAnalysisTime;
    }
    /**
     * @return mixed[]
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
    /**
     * @return array<string, list<Error>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /**
     * @return array<string, list<Error>>
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
     * @return CollectorData
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getUsedTraitDependencies(): array
    {
        return $this->usedTraitDependencies;
    }
    /**
     * @return array<string, array{string, string, string, list<string>, list<string>}>
     */
    public function getValueDependencies(): array
    {
        return $this->valueDependencies;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getPackageDependencies(): array
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
    /**
     * The exported nodes carried over from the cache file undecoded, for the files not in getExportedNodes().
     */
    public function getCachedExportedNodes(): \PHPStan\Analyser\ResultCache\CachedExportedNodes
    {
        return $this->cachedExportedNodes;
    }
    /**
     * @return array<string, array{string, bool, string}>
     */
    public function getProjectExtensionFiles(): array
    {
        return $this->projectExtensionFiles;
    }
    /**
     * @return array<string, string>
     */
    public function getCurrentFileHashes(): array
    {
        return $this->currentFileHashes;
    }
}
