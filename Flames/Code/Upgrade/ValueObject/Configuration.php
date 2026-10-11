<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

use Flames\Code\Upgrade\ChangesReporting\Output\ConsoleOutputFormatter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\ValueObject\Configuration\LevelOverflow;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class Configuration
{
    /**
     * @param string[] $fileExtensions
     * @param string[] $paths
     * @param string[] $onlyRules
     * @param LevelOverflow[] $levelOverflows
     * @param string[] $filters
     */
    public function __construct(
        private bool $isDryRun = \false,
        private bool $showProgressBar = \true,
        private bool $shouldClearCache = \false,
        private string $outputFormat = ConsoleOutputFormatter::NAME,
        /**
         * @readonly
         */
        private array $fileExtensions = ['php'],
        /**
         * @readonly
         */
        private array $paths = [],
        private bool $showDiffs = \true,
        private ?string $parallelPort = null,
        private ?string $parallelIdentifier = null,
        private bool $isParallel = \false,
        private ?string $memoryLimit = null,
        private bool $isDebug = \false,
        private bool $reportingWithRealPath = \false,
        /**
         * @readonly
         */
        private array $onlyRules = [],
        private ?string $onlySuffix = null,
        /**
         * @readonly
         */
        private array $levelOverflows = [],
        private bool $showRulesSummary = \false,
        private bool $isComposerBased = \false,
        private bool $isPhpOnly = \false,
        /**
         * @readonly
         */
        private array $filters = [],
        private ?int $maxChanges = null
    )
    {
    }
    public function getMaxChanges(): ?int
    {
        return $this->maxChanges;
    }
    public function isComposerBased(): bool
    {
        return $this->isComposerBased;
    }
    public function isPhpOnly(): bool
    {
        return $this->isPhpOnly;
    }
    public function isDryRun(): bool
    {
        return $this->isDryRun;
    }
    public function shouldShowProgressBar(): bool
    {
        return $this->showProgressBar;
    }
    public function shouldClearCache(): bool
    {
        return $this->shouldClearCache;
    }
    /**
     * @return string[]
     */
    public function getFileExtensions(): array
    {
        Assert::notEmpty($this->fileExtensions);
        return $this->fileExtensions;
    }
    /**
     * @return string[]
     */
    public function getOnlyRules(): array
    {
        return $this->onlyRules;
    }
    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }
    public function shouldShowDiffs(): bool
    {
        return $this->showDiffs;
    }
    public function getParallelPort(): ?string
    {
        return $this->parallelPort;
    }
    public function getParallelIdentifier(): ?string
    {
        return $this->parallelIdentifier;
    }
    public function isParallel(): bool
    {
        return $this->isParallel;
    }
    public function getMemoryLimit(): ?string
    {
        return $this->memoryLimit;
    }
    public function isDebug(): bool
    {
        return $this->isDebug;
    }
    public function isReportingWithRealPath(): bool
    {
        return $this->reportingWithRealPath;
    }
    public function getOnlySuffix(): ?string
    {
        return $this->onlySuffix;
    }
    /**
     * @return string[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
    /**
     * @return LevelOverflow[]
     */
    public function getLevelOverflows(): array
    {
        return $this->levelOverflows;
    }
    /**
     * @return string[]
     */
    public function getBothSetAndRulesDuplicatedRegistrations(): array
    {
        $rootStandaloneRegisteredRules = SimpleParameterProvider::provideArrayParameter(Option::ROOT_STANDALONE_REGISTERED_RULES);
        $setRegisteredRules = SimpleParameterProvider::provideArrayParameter(Option::SET_REGISTERED_RULES);
        $ruleDuplicatedRegistrations = array_intersect($rootStandaloneRegisteredRules, $setRegisteredRules);
        return array_unique($ruleDuplicatedRegistrations);
    }
    public function shouldShowRulesSummary(): bool
    {
        return $this->showRulesSummary;
    }
}
