<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\FileNode;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_unique;
use function array_values;
use function count;
use function error_reporting;
use function hash;
use function is_dir;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
/**
 * @phpstan-import-type CollectorData from CollectedData
 */
#[AutowiredService]
final class FileAnalyser
{
    private \PHPStan\Analyser\ScopeFactory $scopeFactory;
    private \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver;
    private Parser $parser;
    /**
     * @var ExtensionsCollection<IgnoreErrorExtension>
     */
    private ExtensionsCollection $ignoreErrorExtensions;
    private \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer;
    private \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor;
    private bool $reportIgnoresWithoutComments;
    private \PHPStan\Analyser\ValueDependencyCollector $valueDependencyCollector;
    private DependencyResolver $dependencyResolver;
    /** @var array<string, Error> */
    private array $allPhpErrors = [];
    /** @var array<string, Error> */
    private array $filteredPhpErrors = [];
    /**
     * @param ExtensionsCollection<IgnoreErrorExtension> $ignoreErrorExtensions
     */
    public function __construct(
        \PHPStan\Analyser\ScopeFactory $scopeFactory,
        #[AutowiredParameter('@' . \PHPStan\Analyser\NodeScopeResolver::class)]
        \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver,
        #[AutowiredParameter('@defaultAnalysisParser')]
        Parser $parser,
        #[AutowiredExtensions(\PHPStan\Analyser\IgnoreErrorExtension::class)]
        ExtensionsCollection $ignoreErrorExtensions,
        \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer,
        \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor,
        #[AutowiredParameter]
        bool $reportIgnoresWithoutComments,
        \PHPStan\Analyser\ValueDependencyCollector $valueDependencyCollector,
        DependencyResolver $dependencyResolver
    )
    {
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->parser = $parser;
        $this->ignoreErrorExtensions = $ignoreErrorExtensions;
        $this->ruleErrorTransformer = $ruleErrorTransformer;
        $this->localIgnoresProcessor = $localIgnoresProcessor;
        $this->reportIgnoresWithoutComments = $reportIgnoresWithoutComments;
        $this->valueDependencyCollector = $valueDependencyCollector;
        $this->dependencyResolver = $dependencyResolver;
    }
    /**
     * @param array<string, true> $analysedFiles
     * @param callable(Node $node, Scope $scope): void|null $outerNodeCallback
     */
    public function analyseFile(string $file, array $analysedFiles, RuleRegistry $ruleRegistry, CollectorRegistry $collectorRegistry, ?callable $outerNodeCallback): \PHPStan\Analyser\FileAnalyserResult
    {
        /** @var list<Error> $fileErrors */
        $fileErrors = [];
        /** @var list<string> $processedFiles */
        $processedFiles = [];
        /** @var list<Error> $locallyIgnoredErrors */
        $locallyIgnoredErrors = [];
        /** @var CollectorData $fileCollectedData */
        $fileCollectedData = [];
        $fileDependencies = [];
        $usedTraitFileDependencies = [];
        $filePackageDependencies = [];
        $exportedNodes = [];
        $linesToIgnore = [];
        $unmatchedLineIgnores = [];
        $this->valueDependencyCollector->startFile($file);
        if (is_file($file)) {
            try {
                $this->collectErrors($analysedFiles);
                $parserNodes = $this->parser->parseFile($file);
                $processedFiles[] = $file;
                $nodeCallback = new \PHPStan\Analyser\FileAnalyserCallback($file, $ruleRegistry, $collectorRegistry, $outerNodeCallback, $parserNodes, $this->ignoreErrorExtensions->getAll(), $this->parser, $this->ruleErrorTransformer, $processedFiles, $this->dependencyResolver);
                $scope = $this->scopeFactory->create(\PHPStan\Analyser\ScopeContext::create($file), $nodeCallback);
                $nodeCallback(new FileNode($parserNodes), $scope);
                $this->nodeScopeResolver->resetPerFileAnalysisState();
                $dependencies = $this->nodeScopeResolver->processFileNodes($parserNodes, $scope, $nodeCallback);
                $fileErrors = $nodeCallback->fileErrors;
                $fileCollectedData = $nodeCallback->fileCollectedData;
                $resolvedDependencies = $this->dependencyResolver->resolveFileDependencies($dependencies, $analysedFiles);
                $fileDependencies = $resolvedDependencies->fileDependencies;
                $usedTraitFileDependencies = $resolvedDependencies->usedTraitFileDependencies;
                $filePackageDependencies = $resolvedDependencies->packageDependencies;
                foreach ($resolvedDependencies->classReflections as $classReflection) {
                    // what an extension described the class with may depend on something - see DeclarationDependencyTracker
                    $this->valueDependencyCollector->noteClassDependency($classReflection);
                }
                $exportedNodes = $nodeCallback->exportedNodes;
                $linesToIgnore = $nodeCallback->linesToIgnore;
                $unmatchedLineIgnores = $nodeCallback->unmatchedLineIgnores;
                $temporaryFileErrors = $nodeCallback->temporaryFileErrors;
                $processedFiles = $nodeCallback->processedFiles;
                if ($this->reportIgnoresWithoutComments) {
                    foreach ($linesToIgnore as $ignoredFile => $lines) {
                        foreach ($lines as $line => $identifiers) {
                            if ($identifiers === null) {
                                $fileErrors[] = (new \PHPStan\Analyser\Error('Ignoring all errors on a line is not allowed.', $ignoredFile, $line, \false, $ignoredFile, null, 'Use @phpstan-ignore with an identifier and a comment (in parentheses).'))->withIdentifier('ignore.allLineErrors');
                                unset($linesToIgnore[$ignoredFile][$line]);
                                unset($unmatchedLineIgnores[$ignoredFile][$line]);
                                continue;
                            }
                            foreach ($identifiers as $identifier) {
                                if ($identifier['comment'] !== null) {
                                    continue;
                                }
                                $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Ignore with identifier %s has no comment.', $identifier['name']), $ignoredFile, $line, \false, $ignoredFile, null, 'Explain why this ignore is necessary in parentheses after the identifier.'))->withIdentifier('ignore.noComment');
                                unset($linesToIgnore[$ignoredFile][$line]);
                                unset($unmatchedLineIgnores[$ignoredFile][$line]);
                            }
                        }
                    }
                }
                $localIgnoresProcessorResult = $this->localIgnoresProcessor->process($temporaryFileErrors, $linesToIgnore, $unmatchedLineIgnores);
                foreach ($localIgnoresProcessorResult->fileErrors as $fileError) {
                    $fileErrors[] = $fileError;
                }
                foreach ($localIgnoresProcessorResult->locallyIgnoredErrors as $locallyIgnoredError) {
                    $locallyIgnoredErrors[] = $locallyIgnoredError;
                }
                $linesToIgnore = $localIgnoresProcessorResult->linesToIgnore;
                $unmatchedLineIgnores = $localIgnoresProcessorResult->unmatchedLineIgnores;
            } catch (\PhpParser\Error $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error($e->getRawMessage(), $file, $e->getStartLine() !== -1 ? $e->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
            } catch (ParserErrorsException $e) {
                foreach ($e->errors as $error) {
                    $fileErrors[] = (new \PHPStan\Analyser\Error($error->getMessage(), $e->parsedFile ?? $file, $error->getLine() !== -1 ? $error->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
                }
            } catch (AnalysedCodeException $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error($e->getMessage(), $file, \null, $e, \null, \null, $e->getTip()))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            } catch (IdentifierNotFound $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->name), $file, \null, $e, \null, \null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            } catch (UnableToCompileNode|CircularReference $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s', $e->getMessage()), $file, \null, $e))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            } finally {
                $this->restoreCollectErrorsHandler();
            }
        } elseif (is_dir($file)) {
            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('File %s is a directory.', $file), $file, \null, \false))->withIdentifier('phpstan.path');
        } else {
            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('File %s does not exist.', $file), $file, \null, \false))->withIdentifier('phpstan.path');
        }
        foreach ($linesToIgnore as $fileKey => $lines) {
            if (count($lines) > 0) {
                continue;
            }
            unset($linesToIgnore[$fileKey]);
        }
        foreach ($unmatchedLineIgnores as $fileKey => $lines) {
            if (count($lines) > 0) {
                continue;
            }
            unset($unmatchedLineIgnores[$fileKey]);
        }
        return new \PHPStan\Analyser\FileAnalyserResult($fileErrors, array_values($this->filteredPhpErrors), array_values($this->allPhpErrors), $locallyIgnoredErrors, $fileCollectedData, array_values(array_unique($fileDependencies)), array_values(array_unique($usedTraitFileDependencies)), $this->valueDependencyCollector->finishFile(), array_values(array_unique($filePackageDependencies)), $exportedNodes, $linesToIgnore, $unmatchedLineIgnores, $processedFiles);
    }
    /**
     * @param array<string, true> $analysedFiles
     */
    private function collectErrors(array $analysedFiles): void
    {
        $this->filteredPhpErrors = [];
        $this->allPhpErrors = [];
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($analysedFiles): bool {
            if ((error_reporting() & $errno) === 0) {
                // silence @ operator
                return \true;
            }
            $errorMessage = sprintf('%s: %s', $this->getErrorLabel($errno), $errstr);
            $errorSignature = hash('sha256', sprintf('%s:%s::%s', $errfile, $errline, $errorMessage));
            $this->allPhpErrors[$errorSignature] = (new \PHPStan\Analyser\Error($errorMessage, $errfile, $errline, \false))->withIdentifier('phpstan.php');
            if ($errno === E_DEPRECATED) {
                return \true;
            }
            if (!isset($analysedFiles[$errfile])) {
                return \true;
            }
            $this->filteredPhpErrors[$errorSignature] = (new \PHPStan\Analyser\Error($errorMessage, $errfile, $errline, $errno === E_USER_DEPRECATED))->withIdentifier('phpstan.php');
            return \true;
        });
    }
    private function restoreCollectErrorsHandler(): void
    {
        restore_error_handler();
    }
    private function getErrorLabel(int $errno): string
    {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse error';
            case E_NOTICE:
                return 'Notice';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_ERROR:
                return 'User error (E_USER_ERROR)';
            case E_USER_WARNING:
                return 'User warning (E_USER_WARNING)';
            case E_USER_NOTICE:
                return 'User notice (E_USER_NOTICE)';
            case E_USER_DEPRECATED:
                return 'Deprecated (E_USER_DEPRECATED)';
            case E_STRICT:
                return 'Strict error (E_STRICT)';
        }
        return 'Unknown PHP error';
    }
}
