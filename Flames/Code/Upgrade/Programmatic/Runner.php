<?php

declare(strict_types=1);

namespace Flames\Code\Upgrade\Programmatic;

use Flames\Code\Exception\UpgradeFailedException;
use Flames\Code\Upgrade\Application\ApplicationFileProcessor;
use Flames\Code\Upgrade\Autoloading\AdditionalAutoloader;
use Flames\Code\Upgrade\Caching\Detector\ChangedFilesDetector;
use Flames\Code\Upgrade\ChangesReporting\Output\ConsoleOutputFormatter;
use Flames\Code\Upgrade\ChangesReporting\Output\Factory\JsonOutputFactory;
use Flames\Code\Upgrade\Configuration\ConfigurationFactory;
use Flames\Code\Upgrade\Configuration\ConfigurationRuleFilter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DependencyInjection\UpgradeContainerFactory;
use Flames\Code\Upgrade\StaticReflection\DynamicSourceLocatorDecorator;
use Flames\Code\Upgrade\Console\Command\ProcessCommand;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\ArrayInput;
use Flames\Code\Upgrade\Util\MemoryLimiter;
use Flames\Code\Upgrade\ValueObject\Bootstrap\BootstrapConfigs;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\ProcessResult;

/**
 * Runs code upgrades in-process, without invoking the code-upgrade CLI binary.
 *
 * @internal Use {@see \Flames\Code\Upgrade} instead.
 */
final class Runner
{
    /** @var array<string, string> normalized suffix (e.g. "85") => LevelSetList constant name */
    private const array LEVEL_SETS = [
        '53' => 'UP_TO_PHP_53',
        '54' => 'UP_TO_PHP_54',
        '55' => 'UP_TO_PHP_55',
        '56' => 'UP_TO_PHP_56',
        '70' => 'UP_TO_PHP_70',
        '71' => 'UP_TO_PHP_71',
        '72' => 'UP_TO_PHP_72',
        '73' => 'UP_TO_PHP_73',
        '74' => 'UP_TO_PHP_74',
        '80' => 'UP_TO_PHP_80',
        '81' => 'UP_TO_PHP_81',
        '82' => 'UP_TO_PHP_82',
        '83' => 'UP_TO_PHP_83',
        '84' => 'UP_TO_PHP_84',
        '85' => 'UP_TO_PHP_85',
        '86' => 'UP_TO_PHP_86',
    ];

    private static bool $bootstrapped = false;

    public function upgrade(string $version, string $path = 'App', bool $clearCache = false): void
    {
        $processResult = $this->run($version, $path, dryRun: false, clearCache: $clearCache);

        if ($processResult->getSystemErrors() !== []) {
            throw UpgradeFailedException::fromProcessResult($processResult);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $version, string $path = 'App'): array
    {
        $processResult = $this->run($version, $path, dryRun: true, clearCache: false);

        return $this->toArray($processResult, $this->lastConfiguration);
    }

    private ?Configuration $lastConfiguration = null;

    private function run(string $version, string $path, bool $dryRun, bool $clearCache): ProcessResult
    {
        $codePackageRoot = $this->resolveCodePackageRoot();
        $upgradeDir = $this->resolveUpgradeDir();
        $projectRoot = $this->resolveProjectRoot();

        $this->bootstrap($upgradeDir, $projectRoot);

        $targetPath = $this->resolveTargetPath($projectRoot, $path);
        $setConstant = $this->resolveLevelSet($version);
        $configFile = $this->writeConfig(
            $projectRoot,
            $codePackageRoot,
            $targetPath,
            $setConstant,
        );

        $container = (new UpgradeContainerFactory())->createFromBootstrapConfigs(
            new BootstrapConfigs($configFile, []),
        );

        /** @var ProcessCommand $processCommand */
        $processCommand = $container->make(ProcessCommand::class);
        $input = $this->createInput($processCommand, $dryRun);
        $configuration = $this->prepareRun($container, $input, $targetPath, $clearCache);
        $this->lastConfiguration = $configuration;

        /** @var ApplicationFileProcessor $applicationFileProcessor */
        $applicationFileProcessor = $container->make(ApplicationFileProcessor::class);

        return $applicationFileProcessor->run($configuration, $input);
    }

    private function prepareRun(
        UpgradeConfig $container,
        ArrayInput $input,
        string $targetPath,
        bool $clearCache,
    ): Configuration {
        /** @var ConfigurationFactory $configurationFactory */
        $configurationFactory = $container->make(ConfigurationFactory::class);
        /** @var ConfigurationRuleFilter $configurationRuleFilter */
        $configurationRuleFilter = $container->make(ConfigurationRuleFilter::class);
        /** @var MemoryLimiter $memoryLimiter */
        $memoryLimiter = $container->make(MemoryLimiter::class);
        /** @var AdditionalAutoloader $additionalAutoloader */
        $additionalAutoloader = $container->make(AdditionalAutoloader::class);
        /** @var DynamicSourceLocatorDecorator $dynamicSourceLocatorDecorator */
        $dynamicSourceLocatorDecorator = $container->make(DynamicSourceLocatorDecorator::class);
        /** @var ChangedFilesDetector $changedFilesDetector */
        $changedFilesDetector = $container->make(ChangedFilesDetector::class);

        $configuration = $configurationFactory->createFromInput($input);
        $memoryLimiter->adjust($configuration);
        $configurationRuleFilter->setConfiguration($configuration);

        if ($clearCache) {
            $changedFilesDetector->clear();
        }

        $additionalAutoloader->autoloadInput($input);
        $dynamicSourceLocatorDecorator->addPaths([$targetPath]);

        if (!$configuration->isParallel()) {
            $additionalAutoloader->autoloadPaths();
        }

        if ($dynamicSourceLocatorDecorator->arePathsEmpty()) {
            throw new \RuntimeException(sprintf('Target path does not contain upgradeable files: %s', $targetPath));
        }

        return $configuration;
    }

    private function createInput(ProcessCommand $processCommand, bool $dryRun): ArrayInput
    {
        $parameters = [
            'source' => [],
            '--no-progress-bar' => true,
            '--output-format' => ConsoleOutputFormatter::NAME,
        ];

        if ($dryRun) {
            $parameters['--' . Option::DRY_RUN] = true;
        }

        $input = new ArrayInput($parameters);
        $input->bind($processCommand->getDefinition());
        $input->setInteractive(false);

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ProcessResult $processResult, ?Configuration $configuration): array
    {
        $configuration ??= new Configuration(
            isDryRun: true,
            showProgressBar: false,
            shouldClearCache: false,
            outputFormat: ConsoleOutputFormatter::NAME,
            fileExtensions: ['php'],
            paths: [],
            showDiffs: true,
            reportingWithRealPath: true,
        );

        $json = JsonOutputFactory::create($processResult, $configuration);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function bootstrap(string $upgradeResourcesDir, string $projectRoot): void
    {
        if (self::$bootstrapped) {
            return;
        }

        @ini_set('memory_limit', '-1');

        if (! defined('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__')) {
            require_once $upgradeResourcesDir . 'bootstrap-thirdparty.php';
        }

        if (is_file($upgradeResourcesDir . 'preload.php') && is_dir($upgradeResourcesDir . 'thirdparty')) {
            require_once $upgradeResourcesDir . 'preload.php';
        }

        require_once $upgradeResourcesDir . 'bootstrap-phpstan.php';

        $bootstrapFile = $upgradeResourcesDir . 'bootstrap-flames-project.php';

        if (is_file($bootstrapFile)) {
            require_once $bootstrapFile;
        } elseif (is_file($projectRoot . 'vendor/autoload.php')) {
            require_once $projectRoot . 'vendor/autoload.php';
        }

        self::$bootstrapped = true;
    }

    private function resolveUpgradeDir(): string
    {
        $upgradeDir = realpath(__DIR__ . '/../../../../resources/upgrade');

        if ($upgradeDir === false || ! is_dir($upgradeDir)) {
            throw new \RuntimeException('flamesphp/code upgrade resources were not found.');
        }

        return rtrim($upgradeDir, '/') . '/';
    }

    private function resolveCodePackageRoot(): string
    {
        return rtrim(dirname($this->resolveUpgradeDir(), 2), '/') . '/';
    }

    private function resolveProjectRoot(): string
    {
        if (defined('ROOT_PATH')) {
            return rtrim((string) ROOT_PATH, '/') . '/';
        }

        $dir = getcwd() ?: '.';

        while ($dir !== dirname($dir)) {
            if (is_dir($dir . '/vendor/flamesphp/code')) {
                return rtrim(realpath($dir) ?: $dir, '/') . '/';
            }

            $dir = dirname($dir);
        }

        throw new \RuntimeException(
            'Could not resolve project root. Define ROOT_PATH or run from a Flames project directory.',
        );
    }

    private function resolveTargetPath(string $projectRoot, string $path): string
    {
        if ($path !== '' && $path[0] === '/') {
            $absolute = $path;
        } else {
            $absolute = $projectRoot . ltrim(str_replace('\\', '/', $path), '/');
        }

        $real = realpath($absolute);
        if ($real === false || (! is_dir($real) && ! is_file($real))) {
            throw new \InvalidArgumentException(sprintf('Path not found: %s', $path));
        }

        return is_dir($real) ? rtrim($real, '/') . '/' : $real;
    }

    private function resolveLevelSet(string $version): string
    {
        $suffix = $this->normalizePhpVersionSuffix($version);

        if (! isset(self::LEVEL_SETS[$suffix])) {
            $available = array_map(
                fn (string $key): string => $this->formatPhpVersionLabel('UP_TO_PHP_' . $key),
                array_keys(self::LEVEL_SETS),
            );

            throw new \InvalidArgumentException(sprintf(
                'Unsupported PHP version "%s". Supported: %s',
                $version,
                implode(', ', $available),
            ));
        }

        return self::LEVEL_SETS[$suffix];
    }

    private function normalizePhpVersionSuffix(string $version): string
    {
        $version = trim($version);

        if (preg_match('/^(\d+)\.(\d+)$/', $version, $matches) === 1) {
            return $matches[1] . $matches[2];
        }

        if (preg_match('/^(\d{2,3})$/', $version, $matches) === 1) {
            return $matches[1];
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid PHP version "%s". Use formats like 8.5, 8.0, 7.4 or 85.',
            $version,
        ));
    }

    private function formatPhpVersionLabel(string $constant): string
    {
        $suffix = substr($constant, strlen('UP_TO_PHP_'));

        return strlen($suffix) === 2
            ? $suffix[0] . '.' . $suffix[1]
            : $suffix;
    }

    private function writeConfig(
        string $projectRoot,
        string $codePackageRoot,
        string $path,
        string $setConstant,
    ): string {
        $cacheDir = $this->resolveCacheDir($projectRoot);

        foreach ([
            $cacheDir,
            $cacheDir . 'code-upgrade-cache',
            $cacheDir . 'code-upgrade-container',
        ] as $directory) {
            $this->ensureWritableDirectory($directory);
        }

        $configFile = $cacheDir . 'code-upgrade.php';
        $upgradeCacheDir = $cacheDir . 'code-upgrade-cache';
        $containerCacheDir = $cacheDir . 'code-upgrade-container';
        $bootstrapFile = $codePackageRoot . 'resources/upgrade/bootstrap-flames-project.php';
        $vendorFlamesPath = $projectRoot . 'vendor/flamesphp';
        $appPath = $projectRoot . 'App';

        $content = <<<PHP
<?php

declare(strict_types=1);

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Set\ValueObject\LevelSetList;

return UpgradeConfig::configure()
    ->withoutParallel()
    ->withPaths([
        {$this->exportPath($path)},
    ])
    ->withSets([
        LevelSetList::{$setConstant},
    ])
    ->withAutoloadPaths([
        {$this->exportPath($vendorFlamesPath)},
        {$this->exportPath($appPath)},
    ])
    ->withBootstrapFiles([
        {$this->exportPath($bootstrapFile)},
    ])
    ->withSkip([
        '**/installed.php',
        '**/autoload_classmap.php',
        '**/autoload_static.php',
        '**/autoload_files.php',
        '**/autoload_psr4.php',
        '**/autoload_namespaces.php',
        '**/autoload_real.php',
    ])
    ->withCache({$this->exportPath($upgradeCacheDir)}, null, {$this->exportPath($containerCacheDir)});

PHP;

        if (@file_put_contents($configFile, $content) === false) {
            throw new \RuntimeException('Cannot write upgrade config: ' . $configFile);
        }

        @chmod($configFile, 0666 & ~umask());
        $this->clearContainerCache($containerCacheDir);

        return $configFile;
    }

    private function resolveCacheDir(string $projectRoot): string
    {
        if (class_exists(\Flames\Framework\Cache::class) && defined('ROOT_PATH')) {
            return rtrim(\Flames\Framework\Cache::getPath(), '/') . '/flames/code-upgrade/';
        }

        return rtrim(sys_get_temp_dir(), '/') . '/flames/code-upgrade/' . md5($projectRoot) . '/';
    }

    private function ensureWritableDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            $mask = umask(0);
            $created = @mkdir($directory, 0777, true);
            umask($mask);

            if (! $created && ! is_dir($directory)) {
                throw new \RuntimeException('Cannot create directory: ' . $directory);
            }
        } elseif (! is_writable($directory)) {
            throw new \RuntimeException('Cannot write in directory: ' . $directory);
        }
    }

    private function exportPath(string $path): string
    {
        return var_export($path, true);
    }

    private function clearContainerCache(string $containerCacheDir): void
    {
        if (! is_dir($containerCacheDir)) {
            return;
        }

        $entries = scandir($containerCacheDir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $containerCacheDir . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
