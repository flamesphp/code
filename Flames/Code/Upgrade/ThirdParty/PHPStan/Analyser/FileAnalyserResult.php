<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
/**
 * @phpstan-type Identifier = array{name: string, comment: string|null}
 * @phpstan-type LinesToIgnore = array<string, array<int, non-empty-list<Identifier>|null>>
 * @phpstan-import-type CollectorData from CollectedData
 * @phpstan-import-type ValueDependencies from ValueDependencyCollector
 */
final class FileAnalyserResult
{
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $errors;
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
     * @var CollectorData
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $collectedData;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $dependencies;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $usedTraitDependencies;
    /**
     * @var ValueDependencies
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $valueDependencies;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $packageDependencies;
    /**
     * @var list<RootExportedNode>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $exportedNodes;
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
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $processedFiles;
    /**
     * @param list<Error> $errors
     * @param list<Error> $filteredPhpErrors
     * @param list<Error> $allPhpErrors
     * @param list<Error> $locallyIgnoredErrors
     * @param CollectorData $collectedData
     * @param list<string> $dependencies
     * @param list<string> $usedTraitDependencies
     * @param ValueDependencies $valueDependencies see ValueDependencyCollector
     * @param list<string> $packageDependencies
     * @param list<RootExportedNode> $exportedNodes
     * @param LinesToIgnore $linesToIgnore
     * @param LinesToIgnore $unmatchedLineIgnores
     * @param list<string> $processedFiles
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $errors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $filteredPhpErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $allPhpErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $locallyIgnoredErrors,
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
        array $linesToIgnore,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $unmatchedLineIgnores,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $processedFiles
    )
    {
        $this->errors = $errors;
        $this->filteredPhpErrors = $filteredPhpErrors;
        $this->allPhpErrors = $allPhpErrors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->usedTraitDependencies = $usedTraitDependencies;
        $this->valueDependencies = $valueDependencies;
        $this->packageDependencies = $packageDependencies;
        $this->exportedNodes = $exportedNodes;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
        $this->processedFiles = $processedFiles;
    }
    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
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
     * @return CollectorData
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    /**
     * @return list<string>
     */
    public function getUsedTraitDependencies(): array
    {
        return $this->usedTraitDependencies;
    }
    /**
     * @return ValueDependencies
     */
    public function getValueDependencies(): array
    {
        return $this->valueDependencies;
    }
    /**
     * @return list<string>
     */
    public function getPackageDependencies(): array
    {
        return $this->packageDependencies;
    }
    /**
     * @return list<RootExportedNode>
     */
    public function getExportedNodes(): array
    {
        return $this->exportedNodes;
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
    /**
     * @return list<string>
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }
}
