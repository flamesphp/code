<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Reporting;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Configuration\VendorMissAnalyseGuard;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\PostRector\Rector\PostRectorInterface;
use Flames\Code\Upgrade\ValueObject\ProcessResult;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
/**
 * @see \Flames\Code\Upgrade\Tests\Reporting\MissConfigurationReporterTest
 */
final readonly class MissConfigurationReporter
{
    public function __construct(private SymfonyStyle $symfonyStyle, private VendorMissAnalyseGuard $vendorMissAnalyseGuard, private \Flames\Code\Upgrade\Reporting\UnusedSkipResolver $unusedSkipResolver)
    {
    }
    /**
     * Reports skips configured via "->withSkip()" that never matched any element during the run.
     * Enabled with "->reportUnusedSkips()".
     */
    public function reportUnusedSkips(ProcessResult $processResult): void
    {
        $unusedSkips = $this->unusedSkipResolver->resolve($processResult);
        if ($unusedSkips === []) {
            return;
        }
        $this->symfonyStyle->warning(sprintf('%s never matched any element. You can remove %s from "->withSkip()"', count($unusedSkips) > 1 ? 'These skips are unused, they' : 'This skip is unused, it', count($unusedSkips) > 1 ? 'them' : 'it'));
        // add a blank line between items, so grouped rule skips stay visually separated
        $spacedUnusedSkips = array_map(static fn(string $unusedSkip): string => $unusedSkip . "\n", $unusedSkips);
        $this->symfonyStyle->listing($spacedUnusedSkips);
    }
    public function reportSkippedNeverRegisteredRules(): int
    {
        $registeredRules = SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_RULES);
        $skippedRules = SimpleParameterProvider::provideArrayParameter(Option::SKIPPED_RECTOR_RULES);
        $neverRegisteredSkippedRules = array_unique(array_diff($skippedRules, $registeredRules));
        // remove special PostRectorInterface rules, they are registered in a different way
        $neverRegisteredSkippedRules = array_filter($neverRegisteredSkippedRules, fn($skippedRule): bool => !is_a($skippedRule, PostRectorInterface::class, \true));
        if ($neverRegisteredSkippedRules === []) {
            return 0;
        }
        $this->symfonyStyle->warning(sprintf('%s never registered. You can remove %s from "->withSkip()"', count($neverRegisteredSkippedRules) > 1 ? 'These skipped rules are' : 'This skipped rule is', count($neverRegisteredSkippedRules) > 1 ? 'them' : 'it'));
        $this->symfonyStyle->listing($neverRegisteredSkippedRules);
        return count($neverRegisteredSkippedRules);
    }
    public function reportSkippedNonRectorClasses(): int
    {
        $skippedNonRectorClasses = SimpleParameterProvider::provideArrayParameter(Option::SKIPPED_NON_RECTOR_CLASSES);
        if ($skippedNonRectorClasses === []) {
            return 0;
        }
        $this->symfonyStyle->warning(sprintf('%s "%s" %s not a Upgrade rule, so %s never be skipped. Only classes that implement "%s" can be used in "->withSkip()"', count($skippedNonRectorClasses) > 1 ? 'These skipped classes' : 'This skipped class', implode('", "', $skippedNonRectorClasses), count($skippedNonRectorClasses) > 1 ? 'are' : 'is', count($skippedNonRectorClasses) > 1 ? 'they can' : 'it can', RectorInterface::class));
        return count($skippedNonRectorClasses);
    }
    /**
     * @param string[] $configuredPaths
     */
    public function reportVendorInPaths(array $configuredPaths): void
    {
        if (!$this->vendorMissAnalyseGuard->isVendorAnalyzed($configuredPaths)) {
            return;
        }
        $this->symfonyStyle->warning(sprintf('Upgrade has detected a "/vendor" directory in your configured paths. If this is Composer\'s vendor directory, this is not necessary as it will be autoloaded. Scanning the Composer /vendor directory will cause Upgrade to run much slower and possibly with errors.%sRemove "/vendor" from Upgrade paths and run again.', "\n\n"));
    }
    public function reportStartWithShortOpenTag(): void
    {
        $files = SimpleParameterProvider::provideArrayParameter(Option::SKIPPED_START_WITH_SHORT_OPEN_TAG_FILES);
        if ($files === []) {
            return;
        }
        $suffix = count($files) > 1 ? 's were' : ' was';
        $fileList = implode("\n", $files);
        $this->symfonyStyle->warning(sprintf('The following file%s skipped as starting with short open tag. Migrate to long open PHP tag first: %s%s', $suffix, "\n\n", $fileList));
        sleep(3);
    }
}
