<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding\ValueObject;

use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
/**
 * @see \Flames\Code\Upgrade\Config\UpgradeConfig::ruleWithConfigurationComposerVersionBound()
 */
final readonly class ComposerBoundRuleConfiguration
{
    /**
     * @param class-string<ConfigurableRectorInterface> $rectorClass
     * @param mixed[] $configuration
     */
    public function __construct(private string $rectorClass, private string $packageName, private string $versionConstraint, private array $configuration, private bool $isActive)
    {
    }
    /**
     * @return class-string<ConfigurableRectorInterface>
     */
    public function getRectorClass(): string
    {
        return $this->rectorClass;
    }
    public function getPackageName(): string
    {
        return $this->packageName;
    }
    public function getVersionConstraint(): string
    {
        return $this->versionConstraint;
    }
    /**
     * @return mixed[]
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
