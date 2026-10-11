<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_merge;
use function count;
use function get_class;
use function sprintf;
#[AutowiredService]
final class AnalyserResultFinalizer
{
    private RuleRegistry $ruleRegistry;
    /**
     * @var ExtensionsCollection<IgnoreErrorExtension>
     */
    private ExtensionsCollection $ignoreErrorExtensions;
    private \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer;
    private \PHPStan\Analyser\ScopeFactory $scopeFactory;
    private \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor;
    private bool $reportUnmatchedIgnoredErrors;
    /**
     * @param ExtensionsCollection<IgnoreErrorExtension> $ignoreErrorExtensions
     */
    public function __construct(
        RuleRegistry $ruleRegistry,
        #[AutowiredExtensions(\PHPStan\Analyser\IgnoreErrorExtension::class)]
        ExtensionsCollection $ignoreErrorExtensions,
        \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer,
        \PHPStan\Analyser\ScopeFactory $scopeFactory,
        \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor,
        #[AutowiredParameter]
        bool $reportUnmatchedIgnoredErrors
    )
    {
        $this->ruleRegistry = $ruleRegistry;
        $this->ignoreErrorExtensions = $ignoreErrorExtensions;
        $this->ruleErrorTransformer = $ruleErrorTransformer;
        $this->scopeFactory = $scopeFactory;
        $this->localIgnoresProcessor = $localIgnoresProcessor;
        $this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
    }
    public function finalize(\PHPStan\Analyser\AnalyserResult $analyserResult, bool $onlyFiles, bool $debug): \PHPStan\Analyser\FinalizerResult
    {
        if (count($analyserResult->collectedData) === 0) {
            return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
        }
        $hasInternalErrors = count($analyserResult->internalErrors) > 0 || $analyserResult->reachedInternalErrorsCountLimit;
        if ($hasInternalErrors) {
            return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
        }
        $nodeType = CollectedDataNode::class;
        $node = new CollectedDataNode($analyserResult->collectedData, $onlyFiles);
        $file = 'N/A';
        $scope = $this->scopeFactory->create(\PHPStan\Analyser\ScopeContext::create($file));
        $tempCollectorErrors = [];
        $internalErrors = $analyserResult->internalErrors;
        foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
            try {
                $ruleErrors = $rule->processNode($node, $scope);
            } catch (AnalysedCodeException $e) {
                $tempCollectorErrors[] = (new \PHPStan\Analyser\Error($e->getMessage(), $file, $node->getStartLine(), $e, \null, \null, $e->getTip()))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                continue;
            } catch (IdentifierNotFound $e) {
                $tempCollectorErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->name), $file, $node->getStartLine(), $e, \null, \null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                continue;
            } catch (UnableToCompileNode|CircularReference $e) {
                $tempCollectorErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                continue;
            } catch (Throwable $t) {
                if ($debug) {
                    throw $t;
                }
                $internalErrors[] = new \PHPStan\Analyser\InternalError($t->getMessage(), sprintf('running CollectedDataNode rule %s', get_class($rule)), \PHPStan\Analyser\InternalError::prepareTrace($t), $t->getTraceAsString(), \true);
                continue;
            }
            foreach ($ruleErrors as $ruleError) {
                $error = $this->ruleErrorTransformer->transform($ruleError, $scope, [], $node);
                if ($error->canBeIgnored === \true) {
                    foreach ($this->ignoreErrorExtensions->getAll() as $ignoreErrorExtension) {
                        if ($ignoreErrorExtension->shouldIgnore($error, $node, $scope)) {
                            continue 2;
                        }
                    }
                }
                $tempCollectorErrors[] = $error;
            }
        }
        $errors = $analyserResult->unorderedErrors;
        $locallyIgnoredErrors = $analyserResult->locallyIgnoredErrors;
        $allLinesToIgnore = $analyserResult->linesToIgnore;
        $allUnmatchedLineIgnores = $analyserResult->unmatchedLineIgnores;
        $collectorErrors = [];
        $locallyIgnoredCollectorErrors = [];
        foreach ($tempCollectorErrors as $tempCollectorError) {
            $file = $tempCollectorError->traitFilePath ?? $tempCollectorError->getFilePath();
            $linesToIgnore = $allLinesToIgnore[$file] ?? [];
            $unmatchedLineIgnores = $allUnmatchedLineIgnores[$file] ?? [];
            $localIgnoresProcessorResult = $this->localIgnoresProcessor->process([$tempCollectorError], $linesToIgnore, $unmatchedLineIgnores);
            foreach ($localIgnoresProcessorResult->fileErrors as $error) {
                $errors[] = $error;
                $collectorErrors[] = $error;
            }
            foreach ($localIgnoresProcessorResult->locallyIgnoredErrors as $locallyIgnoredError) {
                $locallyIgnoredErrors[] = $locallyIgnoredError;
                $locallyIgnoredCollectorErrors[] = $locallyIgnoredError;
            }
            $allLinesToIgnore[$file] = $localIgnoresProcessorResult->linesToIgnore;
            $allUnmatchedLineIgnores[$file] = $localIgnoresProcessorResult->unmatchedLineIgnores;
        }
        return $this->addUnmatchedIgnoredErrors(new \PHPStan\Analyser\AnalyserResult(array_merge($errors, $analyserResult->filteredPhpErrors), [], $analyserResult->allPhpErrors, $locallyIgnoredErrors, $allLinesToIgnore, $allUnmatchedLineIgnores, $internalErrors, $analyserResult->collectedData, $analyserResult->dependencies, $analyserResult->usedTraitDependencies, $analyserResult->valueDependencies, $analyserResult->packageDependencies, $analyserResult->exportedNodes, $analyserResult->reachedInternalErrorsCountLimit, $analyserResult->peakMemoryUsageBytes, $analyserResult->processedFiles, $analyserResult->workerCount), $collectorErrors, $locallyIgnoredCollectorErrors);
    }
    private function mergeFilteredPhpErrors(\PHPStan\Analyser\AnalyserResult $analyserResult): \PHPStan\Analyser\AnalyserResult
    {
        return new \PHPStan\Analyser\AnalyserResult(array_merge($analyserResult->unorderedErrors, $analyserResult->filteredPhpErrors), [], $analyserResult->allPhpErrors, $analyserResult->locallyIgnoredErrors, $analyserResult->linesToIgnore, $analyserResult->unmatchedLineIgnores, $analyserResult->internalErrors, $analyserResult->collectedData, $analyserResult->dependencies, $analyserResult->usedTraitDependencies, $analyserResult->valueDependencies, $analyserResult->packageDependencies, $analyserResult->exportedNodes, $analyserResult->reachedInternalErrorsCountLimit, $analyserResult->peakMemoryUsageBytes, $analyserResult->processedFiles, $analyserResult->workerCount);
    }
    /**
     * @param list<Error> $collectorErrors
     * @param list<Error> $locallyIgnoredCollectorErrors
     */
    private function addUnmatchedIgnoredErrors(\PHPStan\Analyser\AnalyserResult $analyserResult, array $collectorErrors, array $locallyIgnoredCollectorErrors): \PHPStan\Analyser\FinalizerResult
    {
        if (!$this->reportUnmatchedIgnoredErrors) {
            return new \PHPStan\Analyser\FinalizerResult($analyserResult, $collectorErrors, $locallyIgnoredCollectorErrors);
        }
        $errors = $analyserResult->unorderedErrors;
        foreach ($analyserResult->unmatchedLineIgnores as $file => $data) {
            foreach ($data as $ignoredFile => $lines) {
                if ($ignoredFile !== $file) {
                    continue;
                }
                foreach ($lines as $line => $identifiers) {
                    if ($identifiers === null) {
                        $errors[] = (new \PHPStan\Analyser\Error(sprintf('No error to ignore is reported on line %d.', $line), $file, $line, \false, $file))->withIdentifier('ignore.unmatchedLine');
                        continue;
                    }
                    foreach ($identifiers as $identifier) {
                        $errors[] = (new \PHPStan\Analyser\Error(sprintf('No error with identifier %s is reported on line %d.', $identifier['name'], $line), $file, $line, \false, $file))->withIdentifier('ignore.unmatchedIdentifier');
                    }
                }
            }
        }
        return new \PHPStan\Analyser\FinalizerResult(new \PHPStan\Analyser\AnalyserResult($errors, $analyserResult->filteredPhpErrors, $analyserResult->allPhpErrors, $analyserResult->locallyIgnoredErrors, $analyserResult->linesToIgnore, $analyserResult->unmatchedLineIgnores, $analyserResult->internalErrors, $analyserResult->collectedData, $analyserResult->dependencies, $analyserResult->usedTraitDependencies, $analyserResult->valueDependencies, $analyserResult->packageDependencies, $analyserResult->exportedNodes, $analyserResult->reachedInternalErrorsCountLimit, $analyserResult->peakMemoryUsageBytes, $analyserResult->processedFiles, $analyserResult->workerCount), $collectorErrors, $locallyIgnoredCollectorErrors);
    }
}
