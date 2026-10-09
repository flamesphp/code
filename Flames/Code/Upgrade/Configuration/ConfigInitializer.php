<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration;

use FlamesPrefix202610\Nette\Utils\FileSystem;
use Flames\Code\Upgrade\Agentic\TerminalDetector;
use Flames\Code\Upgrade\Bootstrap\UpgradeConfigsResolver;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\FileSystem\InitFilePathsResolver;
use Flames\Code\Upgrade\PostRector\Rector\PostRectorInterface;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
final readonly class ConfigInitializer
{
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(private array $rectors, private InitFilePathsResolver $initFilePathsResolver, private SymfonyStyle $symfonyStyle, private UpgradeConfigsResolver $rectorConfigsResolver)
    {
    }
    public function createConfig(string $projectDirectory): void
    {
        $commonUpgradeConfigPath = $projectDirectory . '/' . UpgradeConfigsResolver::DEFAULT_CONFIG_FILE;
        $distUpgradeConfigPath = $projectDirectory . '/' . UpgradeConfigsResolver::DEFAULT_DIST_CONFIG_FILE;
        if (file_exists($commonUpgradeConfigPath)) {
            $this->symfonyStyle->warning('Register rules or sets in your "' . UpgradeConfigsResolver::DEFAULT_CONFIG_FILE . '" config');
            return;
        }
        if (file_exists($distUpgradeConfigPath)) {
            $this->symfonyStyle->warning('Register rules or sets in your "' . UpgradeConfigsResolver::DEFAULT_DIST_CONFIG_FILE . '" config');
            return;
        }
        $mainConfigFile = $this->rectorConfigsResolver->provide()->getMainConfigFile();
        if ($mainConfigFile !== null && file_exists($mainConfigFile)) {
            $this->symfonyStyle->warning('Register rules or sets in your "' . basename($mainConfigFile) . '" config');
            return;
        }
        // non-interactive terminal, e.g. piped output, CI or an agent: never prompt or silently write a
        // config, just say what to do - Symfony still treats a closed STDIN as interactive here
        if (!TerminalDetector::isInputTty()) {
            $this->symfonyStyle->warning(sprintf('No "%s" config found. Create one, or pass "--config <path>".', UpgradeConfigsResolver::DEFAULT_CONFIG_FILE));
            return;
        }
        $response = $this->symfonyStyle->ask('No "' . UpgradeConfigsResolver::DEFAULT_CONFIG_FILE . '" config found. Should we generate it for you?', 'yes');
        // be tolerant about input
        if (!in_array($response, ['yes', 'YES', 'y', 'Y'], \true)) {
            // okay, nothing we can do
            return;
        }
        $configContents = FileSystem::read(__DIR__ . '/../../../../resources/upgrade/templates/code-upgrade.php.dist');
        $configContents = $this->replacePathsContents($configContents, $projectDirectory);
        FileSystem::write($commonUpgradeConfigPath, $configContents, null);
        $this->symfonyStyle->success('The config is added now. Re-run command to make Upgrade do the work!');
    }
    public function areSomeRectorsLoaded(): bool
    {
        $activeRectors = $this->filterActiveRectors($this->rectors);
        return $activeRectors !== [];
    }
    /**
     * @param RectorInterface[] $rectors
     * @return RectorInterface[]
     */
    private function filterActiveRectors(array $rectors): array
    {
        return array_filter($rectors, static fn(RectorInterface $rector): bool => !$rector instanceof PostRectorInterface);
    }
    private function replacePathsContents(string $rectorPhpTemplateContents, string $projectDirectory): string
    {
        $projectPhpDirectories = $this->initFilePathsResolver->resolve($projectDirectory);
        // fallback to default 'src' in case of empty one
        if ($projectPhpDirectories === []) {
            $projectPhpDirectories[] = 'src';
        }
        $projectPhpDirectoriesContents = '';
        foreach ($projectPhpDirectories as $projectPhpDirectory) {
            $projectPhpDirectoriesContents .= "        __DIR__ . '/" . $projectPhpDirectory . "'," . \PHP_EOL;
        }
        $projectPhpDirectoriesContents = rtrim($projectPhpDirectoriesContents);
        return str_replace('__PATHS__', $projectPhpDirectoriesContents, $rectorPhpTemplateContents);
    }
}
