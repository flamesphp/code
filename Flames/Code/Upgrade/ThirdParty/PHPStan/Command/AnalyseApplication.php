<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\CollectedData;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\BytesHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\ShouldNotHappenException;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function array_unique;
use function count;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function memory_get_peak_usage;
use function microtime;
use function sprintf;
/**
 * @phpstan-import-type CollectorData from CollectedData
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type ValueDependencies from ValueDependencyCollector
 */
#[AutowiredService]
final class AnalyseApplication
{
    private \PHPStan\Command\BootstrapFilesRunner $bootstrapFilesRunner;
    private \PHPStan\Command\AnalyserRunner $analyserRunner;
    private AnalyserResultFinalizer $analyserResultFinalizer;
    private StubValidator $stubValidator;
    private ResultCacheManagerFactory $resultCacheManagerFactory;
    private IgnoredErrorHelper $ignoredErrorHelper;
    private StubFilesProvider $stubFilesProvider;
    public function __construct(\PHPStan\Command\BootstrapFilesRunner $bootstrapFilesRunner, \PHPStan\Command\AnalyserRunner $analyserRunner, AnalyserResultFinalizer $analyserResultFinalizer, StubValidator $stubValidator, ResultCacheManagerFactory $resultCacheManagerFactory, IgnoredErrorHelper $ignoredErrorHelper, StubFilesProvider $stubFilesProvider)
    {
        $this->bootstrapFilesRunner = $bootstrapFilesRunner;
        $this->analyserRunner = $analyserRunner;
        $this->analyserResultFinalizer = $analyserResultFinalizer;
        $this->stubValidator = $stubValidator;
        $this->resultCacheManagerFactory = $resultCacheManagerFactory;
        $this->ignoredErrorHelper = $ignoredErrorHelper;
        $this->stubFilesProvider = $stubFilesProvider;
    }
    /**
     * @param string[] $files
     * @param mixed[]|null $projectConfigArray
     * @throws InceptionNotSuccessfulException
     */
    public function analyse(array $files, bool $onlyFiles, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, bool $defaultLevelUsed, bool $debug, ?string $projectConfigFile, ?array $projectConfigArray, ?string $tmpFile, ?string $insteadOfFile, InputInterface $input): \PHPStan\Command\AnalysisResult
    {
        $isResultCacheUsed = \false;
        $fileReplacements = [];
        if ($tmpFile !== null && $insteadOfFile !== null) {
            $fileReplacements = [$insteadOfFile => $tmpFile];
        }
        $resultCacheManager = $this->resultCacheManagerFactory->create($fileReplacements);
        $resultCacheExisted = \is_file($resultCacheManager->cacheFilePath);
        $ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
        $fileSpecificErrors = [];
        if (count($ignoredErrorHelperResult->errors) > 0) {
            $notFileSpecificErrors = $ignoredErrorHelperResult->errors;
            $internalErrors = [];
            $collectedData = [];
            $savedResultCache = \false;
            $memoryUsageBytes = 0;
            $workerCount = 0;
            $processedFiles = [];
            if ($errorOutput->isVeryVerbose()) {
                $errorOutput->writeLineFormatted('Result cache was not saved because of ignoredErrorHelperResult errors.');
            }
        } else {
            $resultCache = $resultCacheManager->restore($files, $debug, $onlyFiles, $projectConfigArray, $errorOutput);
            $intermediateAnalyserResult = $this->runAnalyser($resultCache->filesToAnalyse, $files, $debug, $projectConfigFile, $tmpFile, $insteadOfFile, $stdOutput, $errorOutput, $input);
            $projectStubFiles = $this->stubFilesProvider->getProjectStubFiles();
            $forceValidateStubFiles = (bool) ($_SERVER['__PHPSTAN_FORCE_VALIDATE_STUB_FILES'] ?? \false);
            if ($resultCache->fullAnalysis && count($projectStubFiles) !== 0 && (!$onlyFiles || $forceValidateStubFiles)) {
                $stubErrors = $this->stubValidator->validate($projectStubFiles, $debug);
                $intermediateAnalyserResult = new AnalyserResult(array_merge($intermediateAnalyserResult->unorderedErrors, $stubErrors), $intermediateAnalyserResult->filteredPhpErrors, $intermediateAnalyserResult->allPhpErrors, $intermediateAnalyserResult->locallyIgnoredErrors, $intermediateAnalyserResult->linesToIgnore, $intermediateAnalyserResult->unmatchedLineIgnores, $intermediateAnalyserResult->internalErrors, $intermediateAnalyserResult->collectedData, $intermediateAnalyserResult->dependencies, $intermediateAnalyserResult->usedTraitDependencies, $intermediateAnalyserResult->valueDependencies, $intermediateAnalyserResult->packageDependencies, $intermediateAnalyserResult->exportedNodes, $intermediateAnalyserResult->reachedInternalErrorsCountLimit, $intermediateAnalyserResult->peakMemoryUsageBytes, $intermediateAnalyserResult->processedFiles, $intermediateAnalyserResult->workerCount);
            }
            $processedFiles = $intermediateAnalyserResult->processedFiles;
            $resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, \true);
            $analyserResult = $this->analyserResultFinalizer->finalize($this->switchTmpFileInAnalyserResult($resultCacheResult->getAnalyserResult(), $insteadOfFile, $tmpFile), $onlyFiles, $debug)->analyserResult;
            $internalErrors = $analyserResult->internalErrors;
            $errors = array_merge($analyserResult->getErrors(), $analyserResult->filteredPhpErrors);
            $hasInternalErrors = count($internalErrors) > 0 || $analyserResult->reachedInternalErrorsCountLimit;
            $memoryUsageBytes = $analyserResult->peakMemoryUsageBytes;
            $workerCount = $analyserResult->workerCount;
            $isResultCacheUsed = !$resultCache->fullAnalysis;
            $ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $files, $hasInternalErrors);
            $fileSpecificErrors = $ignoredErrorHelperProcessedResult->notIgnoredErrors;
            $notFileSpecificErrors = $ignoredErrorHelperProcessedResult->otherIgnoreMessages;
            $collectedData = $analyserResult->collectedData;
            $savedResultCache = $resultCacheResult->saved;
        }
        return new \PHPStan\Command\AnalysisResult($fileSpecificErrors, $notFileSpecificErrors, $internalErrors, [], $this->mapCollectedData($collectedData), $defaultLevelUsed, $projectConfigFile, $savedResultCache, $memoryUsageBytes, $isResultCacheUsed, $processedFiles, $resultCacheExisted, $workerCount);
    }
    /**
     * @param CollectorData $collectedData
     *
     * @return list<CollectedData>
     */
    private function mapCollectedData(array $collectedData): array
    {
        $result = [];
        foreach ($collectedData as $file => $dataPerCollector) {
            foreach ($dataPerCollector as $collectorType => $rawData) {
                $result[] = new CollectedData($rawData, $file, $collectorType);
            }
        }
        return $result;
    }
    /**
     * @param string[] $files
     * @param string[] $allAnalysedFiles
     * @throws InceptionNotSuccessfulException
     */
    private function runAnalyser(array $files, array $allAnalysedFiles, bool $debug, ?string $projectConfigFile, ?string $tmpFile, ?string $insteadOfFile, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, InputInterface $input): AnalyserResult
    {
        $filesCount = count($files);
        $allAnalysedFilesCount = count($allAnalysedFiles);
        if ($filesCount === 0) {
            // nothing to analyse, but the deferred bootstrapFiles still must
            // run in the main thread: the phases that follow may reflect
            // analysed code (stub validation, collector rules from the result
            // cache)
            $this->bootstrapFilesRunner->run($errorOutput, $debug);
            $errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressFinish();
            return new AnalyserResult([], [], [], [], [], [], [], [], [], [], ['values' => [], 'dependents' => []], [], [], \false, 0, []);
        }
        if (!$debug) {
            $preFileCallback = null;
            $postFileCallback = static function (int $step) use ($errorOutput): void {
                $errorOutput->getStyle()->progressAdvance($step);
            };
            $errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
        } else {
            $startTime = null;
            $preFileCallback = static function (string $file) use ($stdOutput, &$startTime): void {
                $stdOutput->writeLineFormatted($file);
                $startTime = microtime(\true);
            };
            $postFileCallback = null;
            if ($stdOutput->isDebug()) {
                $previousMemory = memory_get_peak_usage(\true);
                $postFileCallback = static function (int $step, array $processedFiles = []) use ($stdOutput, &$previousMemory, &$startTime, &$linesOfCode): void {
                    if ($startTime === null) {
                        throw new ShouldNotHappenException();
                    }
                    $currentTotalMemory = memory_get_peak_usage(\true);
                    $elapsedTime = microtime(\true) - $startTime;
                    $linesOfCode = 0;
                    foreach (array_unique($processedFiles) as $processedFile) {
                        $handle = @fopen($processedFile, 'r');
                        if ($handle === \false) {
                            continue;
                        }
                        while (!feof($handle)) {
                            fgets($handle);
                            $linesOfCode++;
                        }
                        fclose($handle);
                    }
                    $stdOutput->writeLineFormatted(sprintf('--- consumed %s, total %s, took %.2f s, %.3f LoC/s', BytesHelper::bytes($currentTotalMemory - $previousMemory), BytesHelper::bytes($currentTotalMemory), $elapsedTime, $linesOfCode / $elapsedTime));
                    $previousMemory = $currentTotalMemory;
                };
            }
        }
        $analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, \true, $projectConfigFile, $tmpFile, $insteadOfFile, $input, $errorOutput);
        if (!$debug) {
            $errorOutput->getStyle()->progressFinish();
        }
        return $analyserResult;
    }
    private function switchTmpFileInAnalyserResult(AnalyserResult $analyserResult, ?string $insteadOfFile, ?string $tmpFile): AnalyserResult
    {
        if ($insteadOfFile === null || $tmpFile === null) {
            return $analyserResult;
        }
        $newCollectedData = [];
        foreach ($analyserResult->collectedData as $file => $data) {
            if ($file === $tmpFile) {
                $file = $insteadOfFile;
            }
            $newCollectedData[$file] = $data;
        }
        $dependencies = null;
        if ($analyserResult->dependencies !== null) {
            $dependencies = $this->switchTmpFileInDependencies($analyserResult->dependencies, $insteadOfFile, $tmpFile);
        }
        $usedTraitDependencies = null;
        if ($analyserResult->usedTraitDependencies !== null) {
            $usedTraitDependencies = $this->switchTmpFileInDependencies($analyserResult->usedTraitDependencies, $insteadOfFile, $tmpFile);
        }
        $valueDependencies = null;
        if ($analyserResult->valueDependencies !== null) {
            $valueDependencies = $this->switchTmpFileInValueDependencies($analyserResult->valueDependencies, $insteadOfFile, $tmpFile);
        }
        $packageDependencies = null;
        if ($analyserResult->packageDependencies !== null) {
            $packageDependencies = $this->switchTmpFileInDependencies($analyserResult->packageDependencies, $insteadOfFile, $tmpFile);
        }
        $exportedNodes = [];
        foreach ($analyserResult->exportedNodes as $file => $fileExportedNodes) {
            if ($file === $tmpFile) {
                $file = $insteadOfFile;
            }
            $exportedNodes[$file] = $fileExportedNodes;
        }
        return new AnalyserResult($this->switchTmpFileInErrors($analyserResult->unorderedErrors, $insteadOfFile, $tmpFile), $this->switchTmpFileInErrors($analyserResult->filteredPhpErrors, $insteadOfFile, $tmpFile), $this->switchTmpFileInErrors($analyserResult->allPhpErrors, $insteadOfFile, $tmpFile), $this->switchTmpFileInErrors($analyserResult->locallyIgnoredErrors, $insteadOfFile, $tmpFile), $this->switchTmpFileInLinesToIgnore($analyserResult->linesToIgnore, $insteadOfFile, $tmpFile), $this->switchTmpFileInLinesToIgnore($analyserResult->unmatchedLineIgnores, $insteadOfFile, $tmpFile), $analyserResult->internalErrors, $newCollectedData, $dependencies, $usedTraitDependencies, $valueDependencies, $packageDependencies, $exportedNodes, $analyserResult->reachedInternalErrorsCountLimit, $analyserResult->peakMemoryUsageBytes, $analyserResult->processedFiles, $analyserResult->workerCount);
    }
    /**
     * @param array<string, array<string>> $dependencies
     * @return array<string, array<string>>
     */
    private function switchTmpFileInDependencies(array $dependencies, string $insteadOfFile, string $tmpFile): array
    {
        $newDependencies = [];
        foreach ($dependencies as $dependencyFile => $dependentFiles) {
            $new = [];
            foreach ($dependentFiles as $file) {
                if ($file === $tmpFile) {
                    $new[] = $insteadOfFile;
                    continue;
                }
                $new[] = $file;
            }
            $key = $dependencyFile;
            if ($key === $tmpFile) {
                $key = $insteadOfFile;
            }
            $newDependencies[$key] = $new;
        }
        return $newDependencies;
    }
    /**
     * @param ValueDependencies $dependencies
     * @return ValueDependencies
     */
    private function switchTmpFileInValueDependencies(array $dependencies, string $insteadOfFile, string $tmpFile): array
    {
        $dependents = [];
        foreach ($dependencies['dependents'] as $dependentFile => $ids) {
            $dependents[$dependentFile === $tmpFile ? $insteadOfFile : $dependentFile] = $ids;
        }
        return ['values' => $dependencies['values'], 'dependents' => $dependents];
    }
    /**
     * @param list<Error> $errors
     * @return list<Error>
     */
    private function switchTmpFileInErrors(array $errors, string $insteadOfFile, string $tmpFile): array
    {
        $newErrors = [];
        foreach ($errors as $error) {
            if ($error->getFilePath() === $tmpFile) {
                $error = $error->changeFilePath($insteadOfFile);
            }
            if ($error->traitFilePath === $tmpFile) {
                $error = $error->changeTraitFilePath($insteadOfFile);
            }
            $newErrors[] = $error;
        }
        return $newErrors;
    }
    /**
     * @param array<string, LinesToIgnore> $linesToIgnore
     * @return array<string, LinesToIgnore>
     */
    private function switchTmpFileInLinesToIgnore(array $linesToIgnore, string $insteadOfFile, string $tmpFile): array
    {
        $newLinesToIgnore = [];
        foreach ($linesToIgnore as $file => $lines) {
            if ($file === $tmpFile) {
                $file = $insteadOfFile;
            }
            $newLines = [];
            foreach ($lines as $f => $line) {
                if ($f === $tmpFile) {
                    $f = $insteadOfFile;
                }
                $newLines[$f] = $line;
            }
            $newLinesToIgnore[$file] = $newLines;
        }
        return $newLinesToIgnore;
    }
}
