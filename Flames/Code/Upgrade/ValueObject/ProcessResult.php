<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ValueObject\Reporting\FileDiff;
use FlamesPrefix202610\Webmozart\Assert\Assert;
final class ProcessResult
{
    /**
     * @param SystemError[] $systemErrors
     * @param FileDiff[] $fileDiffs
     * @param array<string, string[]> $usedSkips
     */
    public function __construct(private array $systemErrors, private readonly array $fileDiffs, private readonly int $totalChanged, private array $usedSkips = [])
    {
        Assert::allIsInstanceOf($this->systemErrors, SystemError::class);
        Assert::allIsInstanceOf($this->fileDiffs, FileDiff::class);
        Assert::allString(array_keys($this->usedSkips));
        foreach ($this->usedSkips as $usedSkip) {
            Assert::allString($usedSkip);
        }
    }
    /**
     * @return SystemError[]
     */
    public function getSystemErrors(): array
    {
        return $this->systemErrors;
    }
    /**
     * @return FileDiff[]
     */
    public function getFileDiffs(bool $onlyWithChanges = \true): array
    {
        if ($onlyWithChanges) {
            return array_filter($this->fileDiffs, fn(FileDiff $fileDiff): bool => $fileDiff->getDiff() !== '');
        }
        return $this->fileDiffs;
    }
    /**
     * @param SystemError[] $systemErrors
     */
    public function addSystemErrors(array $systemErrors): void
    {
        Assert::allIsInstanceOf($systemErrors, SystemError::class);
        $this->systemErrors = array_merge($this->systemErrors, $systemErrors);
    }
    /**
     * Path-only skips are matched while finding files in the main process, but parallel runs build
     * their result from worker processes only. Merge those main-process marks back in, or they would
     * be wrongly reported as unused.
     *
     * @param array<string, string[]> $usedSkips
     */
    public function addUsedSkips(array $usedSkips): void
    {
        foreach ($usedSkips as $skip => $paths) {
            Assert::allString($paths);
            $existingPaths = $this->usedSkips[$skip] ?? [];
            $this->usedSkips[$skip] = array_values(array_unique(array_merge($existingPaths, $paths)));
        }
    }
    public function getTotalChanged(): int
    {
        return $this->totalChanged;
    }
    /**
     * @return array<string, string[]>
     */
    public function getUsedSkips(): array
    {
        return $this->usedSkips;
    }
    /**
     * @return array<class-string<RectorInterface>, int>
     */
    public function getRuleApplicationCounts(): array
    {
        $ruleCounts = [];
        foreach ($this->fileDiffs as $fileDiff) {
            foreach ($fileDiff->getRectorClasses() as $rectorClass) {
                $ruleCounts[$rectorClass] ??= 0;
                ++$ruleCounts[$rectorClass];
            }
        }
        arsort($ruleCounts);
        return $ruleCounts;
    }
}
