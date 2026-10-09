<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration;

use Flames\Code\Upgrade\Agentic\TerminalDetector;
use Flames\Code\Upgrade\ChangesReporting\Output\ConsoleOutputFormatter;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\FileSystem\FilePathFilter;
use Flames\Code\Upgrade\ValueObject\Configuration;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\Configuration\ConfigurationFactoryTest
 */
final readonly class ConfigurationFactory
{
    public function __construct(private SymfonyStyle $symfonyStyle, private \Flames\Code\Upgrade\Configuration\OnlyRuleResolver $onlyRuleResolver, private FilePathFilter $filePathFilter)
    {
    }
    /**
     * @api used in tests
     * @param string[] $paths
     */
    public function createForTests(array $paths): Configuration
    {
        $fileExtensions = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::FILE_EXTENSIONS);
        return new Configuration(\false, \true, \false, ConsoleOutputFormatter::NAME, $fileExtensions, $paths, \true, null, null, \false, null, \false, \false);
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     */
    public function createFromInput(InputInterface $input): Configuration
    {
        $isDryRun = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::DRY_RUN);
        $shouldClearCache = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::CLEAR_CACHE);
        $outputFormat = (string) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::OUTPUT_FORMAT);
        $showProgressBar = $this->shouldShowProgressBar($input, $outputFormat);
        $showDiffs = $this->shouldShowDiffs($input);
        $paths = $this->resolvePaths($input);
        $fileExtensions = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::FILE_EXTENSIONS);
        // filter rule and path
        /** @var string[] $onlyRuleInputs */
        $onlyRuleInputs = (array) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::ONLY);
        $onlyRules = array_map(\Closure::fromCallable($this->onlyRuleResolver->resolve(...)), $onlyRuleInputs);
        $onlySuffix = $input->getOption(\Flames\Code\Upgrade\Configuration\Option::ONLY_SUFFIX);
        if ($onlySuffix !== null) {
            $this->symfonyStyle->warning('The "--only-suffix" option is deprecated and will be removed. Use "--filter" instead, e.g. --filter="*Controller.php"');
        }
        $rawFilter = $input->getOption(\Flames\Code\Upgrade\Configuration\Option::FILTER);
        $filters = $rawFilter !== null ? $this->filePathFilter->parsePatterns((string) $rawFilter) : [];
        // "--only"/"--only-suffix"/"--filter" narrow the run, so skips outside the scope look falsely unused;
        // mark the run as narrowed to disable unused skip reporting and avoid false positives
        if ($onlyRules !== [] || $onlySuffix !== null || $filters !== []) {
            SimpleParameterProvider::setParameter(\Flames\Code\Upgrade\Configuration\Option::IS_RUN_NARROWED, \true);
        }
        $isParallel = SimpleParameterProvider::provideBoolParameter(\Flames\Code\Upgrade\Configuration\Option::PARALLEL);
        $parallelPort = (string) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::PARALLEL_PORT);
        $parallelIdentifier = (string) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::PARALLEL_IDENTIFIER);
        $isDebug = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::DEBUG);
        // using debug disables parallel, so emitting exception is straightforward and easier to debug
        if ($isDebug) {
            $isParallel = \false;
        }
        $maxChanges = $this->resolveMaxChanges($input);
        // a global change counter cannot be shared across parallel workers, so enforce the limit in a single process
        if ($maxChanges !== null) {
            $isParallel = \false;
        }
        $memoryLimit = $this->resolveMemoryLimit($input);
        $isReportingWithRealPath = SimpleParameterProvider::provideBoolParameter(\Flames\Code\Upgrade\Configuration\Option::ABSOLUTE_FILE_PATH);
        $levelOverflows = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::LEVEL_OVERFLOWS);
        $showRulesSummary = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::RULES_SUMMARY);
        $isComposerBased = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::COMPOSER_BASED);
        // "--composer-based" narrows the run the same way "--only" does
        if ($isComposerBased) {
            SimpleParameterProvider::setParameter(\Flames\Code\Upgrade\Configuration\Option::IS_RUN_NARROWED, \true);
        }
        $isPhpOnly = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::PHP);
        // "--php" narrows the run the same way "--only" does
        if ($isPhpOnly) {
            SimpleParameterProvider::setParameter(\Flames\Code\Upgrade\Configuration\Option::IS_RUN_NARROWED, \true);
        }
        return new Configuration($isDryRun, $showProgressBar, $shouldClearCache, $outputFormat, $fileExtensions, $paths, $showDiffs, $parallelPort, $parallelIdentifier, $isParallel, $memoryLimit, $isDebug, $isReportingWithRealPath, $onlyRules, $onlySuffix, $levelOverflows, $showRulesSummary, $isComposerBased, $isPhpOnly, $filters, $maxChanges);
    }
    private function resolveMaxChanges(InputInterface $input): ?int
    {
        $maxChanges = $input->getOption(\Flames\Code\Upgrade\Configuration\Option::MAX_CHANGES);
        if ($maxChanges === null) {
            return null;
        }
        $maxChanges = (int) $maxChanges;
        Assert::positiveInteger($maxChanges);
        return $maxChanges;
    }
    private function shouldShowProgressBar(InputInterface $input, string $outputFormat): bool
    {
        $noProgressBar = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::NO_PROGRESS_BAR);
        if ($noProgressBar) {
            return \false;
        }
        // no interactive terminal, e.g. piped output, CI or an agent - the redraws are just noise
        if (!TerminalDetector::isOutputTty()) {
            return \false;
        }
        if ($this->symfonyStyle->isVerbose()) {
            return \false;
        }
        return $outputFormat === ConsoleOutputFormatter::NAME;
    }
    private function shouldShowDiffs(InputInterface $input): bool
    {
        $noDiffs = (bool) $input->getOption(\Flames\Code\Upgrade\Configuration\Option::NO_DIFFS);
        if ($noDiffs) {
            return \false;
        }
        // fallback to parameter
        return !SimpleParameterProvider::provideBoolParameter(\Flames\Code\Upgrade\Configuration\Option::NO_DIFFS, \false);
    }
    /**
     * @return string[]|mixed[]
     */
    private function resolvePaths(InputInterface $input): array
    {
        $commandLinePaths = (array) $input->getArgument(\Flames\Code\Upgrade\Configuration\Option::SOURCE);
        // give priority to command line
        if ($commandLinePaths !== []) {
            // mark the run as narrowed, so unused skip reporting can be disabled to avoid false positives
            SimpleParameterProvider::setParameter(\Flames\Code\Upgrade\Configuration\Option::IS_RUN_NARROWED, \true);
            $paths = $commandLinePaths;
        } else {
            // fallback to parameter
            $paths = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::PATHS);
        }
        $this->setFilesWithoutExtensionParameter($paths);
        // extensions read the processed paths from here; without this only the test harness
        // sets it, so the parameter is empty in a real run
        SimpleParameterProvider::setParameter(\Flames\Code\Upgrade\Configuration\Option::SOURCE, $paths);
        return $paths;
    }
    /**
     * @param string[] $paths
     */
    private function setFilesWithoutExtensionParameter(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path) && pathinfo($path, \PATHINFO_EXTENSION) === '') {
                $path = realpath($path);
                if ($path === \false) {
                    continue;
                }
                SimpleParameterProvider::addParameter(\Flames\Code\Upgrade\Configuration\Option::FILES_WITHOUT_EXTENSION, $path);
            }
        }
    }
    private function resolveMemoryLimit(InputInterface $input): ?string
    {
        $memoryLimit = $input->getOption(\Flames\Code\Upgrade\Configuration\Option::MEMORY_LIMIT);
        if ($memoryLimit !== null) {
            return (string) $memoryLimit;
        }
        if (!SimpleParameterProvider::hasParameter(\Flames\Code\Upgrade\Configuration\Option::MEMORY_LIMIT)) {
            return null;
        }
        return SimpleParameterProvider::provideStringParameter(\Flames\Code\Upgrade\Configuration\Option::MEMORY_LIMIT);
    }
}
