<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DependencyInjection;

use Flames\Code\Upgrade\Autoloading\BootstrapFilesIncluder;
use Flames\Code\Upgrade\Caching\Detector\ChangedFilesDetector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\NodeTypeResolver\DependencyInjection\PHPStanServicesFactory;
use Flames\Code\Upgrade\ValueObject\Bootstrap\BootstrapConfigs;
final class UpgradeContainerFactory
{
    public function createFromBootstrapConfigs(BootstrapConfigs $bootstrapConfigs): UpgradeConfig
    {
        $rectorConfig = $this->createFromConfigs($bootstrapConfigs->getConfigFiles());
        $mainConfigFile = $bootstrapConfigs->getMainConfigFile();
        if ($mainConfigFile !== null) {
            /** @var ChangedFilesDetector $changedFilesDetector */
            $changedFilesDetector = $rectorConfig->make(ChangedFilesDetector::class);
            $changedFilesDetector->setFirstResolvedConfigFileInfo($mainConfigFile);
        }
        /** @var BootstrapFilesIncluder $bootstrapFilesIncluder */
        $bootstrapFilesIncluder = $rectorConfig->get(BootstrapFilesIncluder::class);
        $bootstrapFilesIncluder->includeBootstrapFiles($rectorConfig->get(PHPStanServicesFactory::class)->getContainer());
        return $rectorConfig;
    }
    /**
     * @param string[] $configFiles
     */
    private function createFromConfigs(array $configFiles): UpgradeConfig
    {
        $lazyContainerFactory = new \Flames\Code\Upgrade\DependencyInjection\LazyContainerFactory();
        $rectorConfig = $lazyContainerFactory->create();
        foreach ($configFiles as $configFile) {
            $rectorConfig->import($configFile);
        }
        $rectorConfig->boot();
        return $rectorConfig;
    }
}
