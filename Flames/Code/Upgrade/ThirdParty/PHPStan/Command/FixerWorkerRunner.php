<?php

declare (strict_types=1);
namespace PHPStan\Command;

use Flames\Code\Upgrade\ThirdParty\Clue\React\NDJson\Encoder;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperResult;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\ShouldNotHappenException;
use Flames\Code\Upgrade\ThirdParty\React\EventLoop\LoopInterface;
use Flames\Code\Upgrade\ThirdParty\React\EventLoop\StreamSelectLoop;
use Flames\Code\Upgrade\ThirdParty\React\Promise\PromiseInterface;
use Flames\Code\Upgrade\ThirdParty\React\Socket\ConnectionInterface;
use Flames\Code\Upgrade\ThirdParty\React\Socket\TcpConnector;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputInterface;
use function array_diff;
use function array_key_exists;
use function count;
use function filemtime;
use function filesize;
use function in_array;
use function is_file;
use function memory_get_peak_usage;
use function Flames\Code\Upgrade\ThirdParty\React\Promise\resolve;
use function sprintf;
use function usort;
use const JSON_INVALID_UTF8_IGNORE;
/**
 * The PHPStan Pro worker logic that runs *after* the application boot.
 *
 * Extracted from FixerWorkerCommand so it can be reused without re-booting: a
 * pcntl_fork()-ed child (see ForkedProcessPromise) inherits the already-booted
 * DI container and calls run() directly, while FixerWorkerCommand still calls
 * it after the expensive CommandHelper::begin() boot of a freshly spawned
 * process.
 *
 * It connects back to FixerApplication's TCP server, restores the result
 * cache, analyses the changed files and streams the results.
 */
#[AutowiredService]
final class FixerWorkerRunner
{
    private IgnoredErrorHelper $ignoredErrorHelper;
    private ResultCacheManagerFactory $resultCacheManagerFactory;
    private AnalyserResultFinalizer $analyserResultFinalizer;
    private ParallelAnalyser $parallelAnalyser;
    private Scheduler $scheduler;
    private CpuCoreCounter $cpuCoreCounter;
    public function __construct(IgnoredErrorHelper $ignoredErrorHelper, ResultCacheManagerFactory $resultCacheManagerFactory, AnalyserResultFinalizer $analyserResultFinalizer, ParallelAnalyser $parallelAnalyser, Scheduler $scheduler, CpuCoreCounter $cpuCoreCounter)
    {
        $this->ignoredErrorHelper = $ignoredErrorHelper;
        $this->resultCacheManagerFactory = $resultCacheManagerFactory;
        $this->analyserResultFinalizer = $analyserResultFinalizer;
        $this->parallelAnalyser = $parallelAnalyser;
        $this->scheduler = $scheduler;
        $this->cpuCoreCounter = $cpuCoreCounter;
    }
    /**
     * @param string[] $inceptionFiles
     * @param mixed[]|null $projectConfigArray
     */
    public function run(\PHPStan\Command\Output $errorOutput, array $inceptionFiles, bool $isOnlyFiles, ?array $projectConfigArray, ?string $configuration, int $serverPort, InputInterface $input): int
    {
        $ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
        if (count($ignoredErrorHelperResult->errors) > 0) {
            throw new ShouldNotHappenException();
        }
        // Always a fresh event loop: in a forked child the parent's inherited
        // loop must never be touched.
        $loop = new StreamSelectLoop();
        $tcpConnector = new TcpConnector($loop);
        $tcpConnector->connect(sprintf('127.0.0.1:%d', $serverPort))->then(function (ConnectionInterface $connection) use ($errorOutput, $inceptionFiles, $isOnlyFiles, $projectConfigArray, $configuration, $input, $ignoredErrorHelperResult, $loop): void {
            // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
            $jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
            // phpcs:enable
            $out = new Encoder($connection, $jsonInvalidUtf8Ignore);
            //$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);
            /** @var ResultCacheManager $resultCacheManager */
            $resultCacheManager = $this->resultCacheManagerFactory->create([]);
            $out->write(['action' => 'analysisStart', 'result' => ['analysedFiles' => $inceptionFiles]]);
            $resultCache = $resultCacheManager->restore($inceptionFiles, \false, \false, $projectConfigArray, $errorOutput);
            $errorsFromResultCacheTmp = $resultCache->errors;
            $locallyIgnoredErrorsFromResultCacheTmp = $resultCache->locallyIgnoredErrors;
            foreach ($resultCache->filesToAnalyse as $fileToAnalyse) {
                unset($errorsFromResultCacheTmp[$fileToAnalyse]);
                unset($locallyIgnoredErrorsFromResultCacheTmp[$fileToAnalyse]);
            }
            $errorsFromResultCache = [];
            foreach ($errorsFromResultCacheTmp as $errorsByFile) {
                foreach ($errorsByFile as $error) {
                    $errorsFromResultCache[] = $error;
                }
            }
            [$errorsFromResultCache, $ignoredErrorsFromResultCache] = $this->filterErrors($errorsFromResultCache, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, \false);
            foreach ($locallyIgnoredErrorsFromResultCacheTmp as $locallyIgnoredErrors) {
                foreach ($locallyIgnoredErrors as $locallyIgnoredError) {
                    $ignoredErrorsFromResultCache[] = [$locallyIgnoredError, null];
                }
            }
            $out->write(['action' => 'analysisStream', 'result' => ['errors' => $errorsFromResultCache, 'ignoredErrors' => $ignoredErrorsFromResultCache, 'analysedFiles' => array_diff($inceptionFiles, $resultCache->filesToAnalyse)]]);
            $filesToAnalyse = $resultCache->filesToAnalyse;
            usort($filesToAnalyse, static function (string $a, string $b): int {
                $aTime = @filemtime($a);
                if ($aTime === \false) {
                    return 1;
                }
                $bTime = @filemtime($b);
                if ($bTime === \false) {
                    return -1;
                }
                // files are sorted from the oldest
                // because ParallelAnalyser reverses the scheduler jobs to do the smallest
                // jobs first
                return $aTime <=> $bTime;
            });
            $this->runAnalyser($loop, $filesToAnalyse, $inceptionFiles, $configuration, $input, function (array $errors, array $locallyIgnoredErrors, array $analysedFiles) use ($out, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles): void {
                $internalErrors = [];
                foreach ($errors as $fileSpecificError) {
                    if (!$fileSpecificError->canBeIgnored instanceof \Throwable) {
                        continue;
                    }
                    $internalErrors[] = $this->transformErrorIntoInternalError($fileSpecificError);
                }
                if (count($internalErrors) > 0) {
                    $out->write(['action' => 'analysisCrash', 'data' => ['internalErrors' => $internalErrors]]);
                    return;
                }
                [$errors, $ignoredErrors] = $this->filterErrors($errors, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, \false);
                foreach ($locallyIgnoredErrors as $locallyIgnoredError) {
                    $ignoredErrors[] = [$locallyIgnoredError, null];
                }
                $out->write(['action' => 'analysisStream', 'result' => ['errors' => $errors, 'ignoredErrors' => $ignoredErrors, 'analysedFiles' => $analysedFiles]]);
            })->then(function (AnalyserResult $intermediateAnalyserResult) use ($resultCacheManager, $resultCache, $errorOutput, $isOnlyFiles, $ignoredErrorHelperResult, $inceptionFiles, $out): void {
                $analyserResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, \false, \true)->analyserResult;
                $finalizerResult = $this->analyserResultFinalizer->finalize($analyserResult, $isOnlyFiles, \false);
                $internalErrors = [];
                foreach ($finalizerResult->getAnalyserResult()->internalErrors as $internalError) {
                    $internalErrors[] = new InternalError($internalError->traceAsString !== null ? sprintf('Internal error: %s', $internalError->message) : $internalError->message, $internalError->contextDescription, $internalError->trace, $internalError->traceAsString, $internalError->shouldReportBug);
                }
                foreach ($finalizerResult->getAnalyserResult()->unorderedErrors as $fileSpecificError) {
                    if (!$fileSpecificError->canBeIgnored instanceof \Throwable) {
                        continue;
                    }
                    $internalErrors[] = $this->transformErrorIntoInternalError($fileSpecificError);
                }
                $hasInternalErrors = count($internalErrors) > 0 || $finalizerResult->getAnalyserResult()->reachedInternalErrorsCountLimit;
                if ($hasInternalErrors) {
                    $out->write(['action' => 'analysisCrash', 'data' => ['internalErrors' => count($internalErrors) > 0 ? $internalErrors : [new InternalError('Internal error occurred', 'running analyser in PHPStan Pro worker', [], null, \false)]]]);
                }
                [$collectorErrors, $ignoredCollectorErrors] = $this->filterErrors($finalizerResult->collectorErrors, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, $hasInternalErrors);
                foreach ($finalizerResult->locallyIgnoredCollectorErrors as $locallyIgnoredCollectorError) {
                    $ignoredCollectorErrors[] = [$locallyIgnoredCollectorError, null];
                }
                $out->write(['action' => 'analysisStream', 'result' => ['errors' => $collectorErrors, 'ignoredErrors' => $ignoredCollectorErrors, 'analysedFiles' => []]]);
                $ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($finalizerResult->analyserResult->getErrors(), $isOnlyFiles, $inceptionFiles, $hasInternalErrors);
                $ignoreFileErrors = [];
                foreach ($ignoredErrorHelperProcessedResult->notIgnoredErrors as $error) {
                    if ($error->identifier === null) {
                        continue;
                    }
                    if (!in_array($error->identifier, ['ignore.count', 'ignore.unmatched', 'ignore.unmatchedLine', 'ignore.unmatchedIdentifier', 'ignore.noComment'], \true)) {
                        continue;
                    }
                    $ignoreFileErrors[] = $error;
                }
                $out->end(['action' => 'analysisEnd', 'result' => ['ignoreFileErrors' => $ignoreFileErrors, 'ignoreNotFileErrors' => $ignoredErrorHelperProcessedResult->otherIgnoreMessages]]);
            });
        });
        $loop->run();
        return 0;
    }
    private function transformErrorIntoInternalError(Error $error): InternalError
    {
        $message = $error->message;
        $metadata = $error->metadata;
        if ($error->identifier === 'phpstan.internal' && array_key_exists(InternalError::STACK_TRACE_AS_STRING_METADATA_KEY, $metadata)) {
            $message = sprintf('Internal error: %s', $message);
        }
        return new InternalError($message, sprintf('analysing file %s', $error->traitFilePath ?? $error->getFilePath()), $metadata[InternalError::STACK_TRACE_METADATA_KEY] ?? [], $metadata[InternalError::STACK_TRACE_AS_STRING_METADATA_KEY] ?? null, \true);
    }
    /**
     * @param string[] $inceptionFiles
     * @param list<Error> $errors
     * @return array{list<Error>, list<array{Error, mixed[]|string}>}
     */
    private function filterErrors(array $errors, IgnoredErrorHelperResult $ignoredErrorHelperResult, bool $onlyFiles, array $inceptionFiles, bool $hasInternalErrors): array
    {
        $ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $inceptionFiles, $hasInternalErrors);
        $finalErrors = [];
        foreach ($ignoredErrorHelperProcessedResult->notIgnoredErrors as $error) {
            if ($error->identifier === null) {
                $finalErrors[] = $error;
                continue;
            }
            if (in_array($error->identifier, ['ignore.count', 'ignore.unmatched'], \true)) {
                continue;
            }
            $finalErrors[] = $error;
        }
        return [$finalErrors, $ignoredErrorHelperProcessedResult->ignoredErrors];
    }
    /**
     * @param string[] $files
     * @param string[] $allAnalysedFiles
     * @param callable(list<Error>, list<Error>, string[]): void $onFileAnalysisHandler
     * @return PromiseInterface<AnalyserResult>
     */
    private function runAnalyser(LoopInterface $loop, array $files, array $allAnalysedFiles, ?string $configuration, InputInterface $input, callable $onFileAnalysisHandler): PromiseInterface
    {
        $filesCount = count($files);
        if ($filesCount === 0) {
            return resolve(new AnalyserResult([], [], [], [], [], [], [], [], [], [], ['values' => [], 'dependents' => []], [], [], \false, memory_get_peak_usage(\true), []));
        }
        $schedule = $this->scheduler->scheduleWork($this->cpuCoreCounter->getNumberOfCpuCores(), $files, static fn(string $file): int => (int) @filesize($file));
        $mainScript = null;
        if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
            $mainScript = $_SERVER['argv'][0];
        }
        return $this->parallelAnalyser->analyse($loop, $schedule, $allAnalysedFiles, $mainScript, null, $configuration, null, null, $input, $onFileAnalysisHandler, null);
    }
}
