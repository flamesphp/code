<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use Closure;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_fill_keys;
use function array_merge;
use function count;
/**
 * @phpstan-import-type CollectorData from CollectedData
 */
#[AutowiredService]
final class Analyser
{
    private \PHPStan\Analyser\FileAnalyser $fileAnalyser;
    private RuleRegistry $ruleRegistry;
    private CollectorRegistry $collectorRegistry;
    private \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver;
    private int $internalErrorsCountLimit;
    public function __construct(
        \PHPStan\Analyser\FileAnalyser $fileAnalyser,
        RuleRegistry $ruleRegistry,
        CollectorRegistry $collectorRegistry,
        #[AutowiredParameter('@' . \PHPStan\Analyser\NodeScopeResolver::class)]
        \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver,
        #[AutowiredParameter]
        int $internalErrorsCountLimit
    )
    {
        $this->fileAnalyser = $fileAnalyser;
        $this->ruleRegistry = $ruleRegistry;
        $this->collectorRegistry = $collectorRegistry;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->internalErrorsCountLimit = $internalErrorsCountLimit;
    }
    /**
     * @param string[] $files
     * @param Closure(string $file): void|null $preFileCallback
     * @param Closure(int, list<string>=): void|null $postFileCallback
     * @param string[]|null $allAnalysedFiles
     */
    public function analyse(array $files, ?Closure $preFileCallback = null, ?Closure $postFileCallback = null, bool $debug = \false, ?array $allAnalysedFiles = null): \PHPStan\Analyser\AnalyserResult
    {
        if ($allAnalysedFiles === null) {
            $allAnalysedFiles = $files;
        }
        $this->nodeScopeResolver->setAnalysedFiles($allAnalysedFiles);
        $allAnalysedFiles = array_fill_keys($allAnalysedFiles, \true);
        /** @var list<Error> $errors */
        $errors = [];
        /** @var list<Error> $filteredPhpErrors */
        $filteredPhpErrors = [];
        /** @var list<Error> $allPhpErrors */
        $allPhpErrors = [];
        /** @var list<Error> $locallyIgnoredErrors */
        $locallyIgnoredErrors = [];
        $linesToIgnore = [];
        $unmatchedLineIgnores = [];
        /** @var CollectorData $collectedData */
        $collectedData = [];
        $internalErrorsCount = 0;
        $reachedInternalErrorsCountLimit = \false;
        $dependencies = [];
        $usedTraitDependencies = [];
        $valueDependencies = ['values' => [], 'dependents' => []];
        $packageDependencies = [];
        $exportedNodes = [];
        $allProcessedFiles = [];
        foreach ($files as $file) {
            if ($preFileCallback !== null) {
                $preFileCallback($file);
            }
            try {
                $fileAnalyserResult = $this->fileAnalyser->analyseFile($file, $allAnalysedFiles, $this->ruleRegistry, $this->collectorRegistry, null);
                $errors = array_merge($errors, $fileAnalyserResult->errors);
                $filteredPhpErrors = array_merge($filteredPhpErrors, $fileAnalyserResult->filteredPhpErrors);
                $allPhpErrors = array_merge($allPhpErrors, $fileAnalyserResult->allPhpErrors);
                $processedFiles = $fileAnalyserResult->processedFiles;
                $allProcessedFiles = array_merge($allProcessedFiles, $processedFiles);
                $locallyIgnoredErrors = array_merge($locallyIgnoredErrors, $fileAnalyserResult->locallyIgnoredErrors);
                $linesToIgnore[$file] = $fileAnalyserResult->linesToIgnore;
                $unmatchedLineIgnores[$file] = $fileAnalyserResult->unmatchedLineIgnores;
                $collectedData = array_merge($collectedData, $fileAnalyserResult->collectedData);
                $dependencies[$file] = $fileAnalyserResult->dependencies;
                $usedTraitDependencies[$file] = $fileAnalyserResult->usedTraitDependencies;
                $valueDependencies = \PHPStan\Analyser\ValueDependencyCollector::merge($valueDependencies, $fileAnalyserResult->valueDependencies);
                $packageDependencies[$file] = $fileAnalyserResult->packageDependencies;
                $fileExportedNodes = $fileAnalyserResult->exportedNodes;
                if (count($fileExportedNodes) > 0) {
                    $exportedNodes[$file] = $fileExportedNodes;
                }
            } catch (Throwable $t) {
                if ($debug) {
                    throw $t;
                }
                $internalErrorsCount++;
                $errors[] = (new \PHPStan\Analyser\Error($t->getMessage(), $file, \null, $t))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($t), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $t->getTraceAsString()]);
                $processedFiles = [$file];
                if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
                    $reachedInternalErrorsCountLimit = \true;
                    break;
                }
            }
            if ($postFileCallback === null) {
                continue;
            }
            $postFileCallback(1, $processedFiles);
        }
        return new \PHPStan\Analyser\AnalyserResult(
            $errors,
            $filteredPhpErrors,
            $allPhpErrors,
            $locallyIgnoredErrors,
            $linesToIgnore,
            $unmatchedLineIgnores,
            [],
            $collectedData,
            $internalErrorsCount === 0 ? $dependencies : null,
            $internalErrorsCount === 0 ? $usedTraitDependencies : null,
            $internalErrorsCount === 0 ? $valueDependencies : null,
            $internalErrorsCount === 0 ? $packageDependencies : null,
            $exportedNodes,
            $reachedInternalErrorsCountLimit,
            // analysis ran in this process - its peak is read where the number is printed
            0,
            $allProcessedFiles
        );
    }
}
