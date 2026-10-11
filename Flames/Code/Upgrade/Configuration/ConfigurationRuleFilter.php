<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration;

use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerBoundRuleConfiguration;
/**
 * Modify available rector rules based on the configuration options
 * @see \Flames\Code\Upgrade\Tests\Configuration\ConfigurationRuleFilterTest
 */
final class ConfigurationRuleFilter
{
    private ?Configuration $configuration = null;
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        // deprecated rules only warn via DeprecatedRulesReporter; they must never run, as their
        // refactor() throws to signal the deprecation - keep them out of the active set
        $rectors = array_values(array_filter($rectors, static fn(RectorInterface $rector): bool => !$rector instanceof DeprecatedInterface));
        if (!$this->configuration instanceof Configuration) {
            return $rectors;
        }
        $onlyRules = $this->configuration->getOnlyRules();
        if ($onlyRules !== []) {
            return $this->filterOnlyRules($rectors, $onlyRules);
        }
        if ($this->configuration->isComposerBased()) {
            return $this->filterComposerBased($rectors);
        }
        if ($this->configuration->isPhpOnly()) {
            return $this->filterPhpOnly($rectors);
        }
        return $rectors;
    }
    /**
     * @param list<RectorInterface> $rectors
     * @param string[] $onlyRules
     * @return list<RectorInterface>
     */
    public function filterOnlyRules(array $rectors, array $onlyRules): array
    {
        $activeRectors = [];
        foreach ($rectors as $rector) {
            foreach ($onlyRules as $onlyRule) {
                if ($rector instanceof $onlyRule) {
                    $activeRectors[] = $rector;
                    break;
                }
            }
        }
        return $activeRectors;
    }
    /**
     * Keeps rules that declare a composer package constraint themselves, and rules whose configuration
     * was registered with a composer package constraint.
     *
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    private function filterComposerBased(array $rectors): array
    {
        $composerBoundRectorClasses = $this->resolveComposerBoundRectorClasses();
        $activeRectors = [];
        foreach ($rectors as $rector) {
            if ($rector instanceof ComposerPackageConstraintInterface) {
                $activeRectors[] = $rector;
                continue;
            }
            if (in_array($rector::class, $composerBoundRectorClasses, \true)) {
                $activeRectors[] = $rector;
            }
        }
        return $activeRectors;
    }
    /**
     * Keeps rules bound to a minimal PHP version, e.g. PHP upgrade rules
     *
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    private function filterPhpOnly(array $rectors): array
    {
        $activeRectors = [];
        foreach ($rectors as $rector) {
            if ($rector instanceof MinPhpVersionInterface) {
                $activeRectors[] = $rector;
            }
        }
        return $activeRectors;
    }
    /**
     * @return string[]
     */
    private function resolveComposerBoundRectorClasses(): array
    {
        $composerBoundRuleConfigurations = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::COMPOSER_BOUND_RULE_CONFIGURATIONS);
        $rectorClasses = [];
        foreach ($composerBoundRuleConfigurations as $composerBoundRuleConfiguration) {
            if (!$composerBoundRuleConfiguration instanceof ComposerBoundRuleConfiguration) {
                continue;
            }
            if (!$composerBoundRuleConfiguration->isActive()) {
                continue;
            }
            $rectorClasses[] = $composerBoundRuleConfiguration->getRectorClass();
        }
        return $rectorClasses;
    }
}
