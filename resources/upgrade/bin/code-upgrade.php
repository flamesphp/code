<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade;

use Flames\Code\Upgrade\ThirdParty\Nette\Json;
use Flames\Code\Upgrade\Bootstrap\AutoloadFileParameterResolver;
use Flames\Code\Upgrade\Bootstrap\UpgradeConfigsResolver;
use Flames\Code\Upgrade\ChangesReporting\Output\JsonOutputFormatter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Console\Style\SymfonyStyleFactory;
use Flames\Code\Upgrade\DependencyInjection\LazyContainerFactory;
use Flames\Code\Upgrade\DependencyInjection\UpgradeContainerFactory;
use Flames\Code\Upgrade\Util\Reflection\PrivatesAccessor;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Application;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\ArgvInput;
// @ intentionally: continue anyway
@\ini_set('memory_limit', '-1');
// Performance boost
\error_reporting(\E_ALL);
\ini_set('display_errors', 'stderr');
\gc_disable();
// Require Composer autoload.php
$autoloadIncluder = new AutoloadIncluder();
$autoloadIncluder->includeDependencyOrRepositoryVendorAutoloadIfExists();
require_once __DIR__ . '/../bootstrap-phpstan.php';
final class AutoloadIncluder
{
    /**
     * @var string[]
     */
    private array $alreadyLoadedAutoloadFiles = [];
    public function includeDependencyOrRepositoryVendorAutoloadIfExists(): void
    {
        if (defined('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__')) {
            return;
        }

        require_once __DIR__ . '/../bootstrap-thirdparty.php';
    }
    /**
     * In case Upgrade is installed as vendor dependency,
     * this autoloads the project vendor/autoload.php, including Rector
     */
    public function autoloadProjectAutoloaderFile(): void
    {
        $this->loadIfExistsAndNotLoadedYet(__DIR__ . '/../../../autoload.php');
    }
    /**
     * In case Upgrade is installed as global dependency
     */
    public function autoloadRectorInstalledAsGlobalDependency(): void
    {
        if (\dirname(__DIR__) === \dirname(\getcwd(), 2)) {
            return;
        }
        if (\is_dir('vendor/flamesphp/code')) {
            return;
        }
        $this->loadIfExistsAndNotLoadedYet('vendor/autoload.php');
    }
    public function autoloadFromCommandLine(): void
    {
        $cliArgs = $_SERVER['argv'];
        $aOptionPosition = \array_search('-a', $cliArgs, \true);
        $autoloadFileOptionPosition = \array_search('--autoload-file', $cliArgs, \true);
        if (\is_int($aOptionPosition)) {
            $autoloadOptionPosition = $aOptionPosition;
        } elseif (\is_int($autoloadFileOptionPosition)) {
            $autoloadOptionPosition = $autoloadFileOptionPosition;
        } else {
            return;
        }
        $autoloadFileValuePosition = $autoloadOptionPosition + 1;
        $fileToAutoload = $cliArgs[$autoloadFileValuePosition] ?? null;
        if ($fileToAutoload === null) {
            return;
        }
        $this->loadIfExistsAndNotLoadedYet($fileToAutoload);
    }
    public function loadIfExistsAndNotLoadedYet(string $filePath): void
    {
        if (!\file_exists($filePath)) {
            return;
        }
        if (\in_array($filePath, $this->alreadyLoadedAutoloadFiles, \true)) {
            return;
        }
        /** @var non-empty-string $realPath always string after file_exists() check */
        $realPath = \realpath($filePath);
        $this->alreadyLoadedAutoloadFiles[] = $realPath;
        require_once $filePath;
    }
}
\class_alias(\Flames\Code\Upgrade\AutoloadIncluder::class, 'AutoloadIncluder', \false);
// require rector-src on split packages
if (\file_exists(__DIR__ . '/../preload-split-package.php') && \is_dir(__DIR__ . '/../../../../vendor')) {
    require_once __DIR__ . '/../preload-split-package.php';
}
$autoloadIncluder->autoloadProjectAutoloaderFile();
$autoloadIncluder->autoloadRectorInstalledAsGlobalDependency();
$autoloadIncluder->autoloadFromCommandLine();
AutoloadFileParameterResolver::resolveFromArgv($_SERVER['argv']);
$rectorConfigsResolver = new UpgradeConfigsResolver();
try {
    $bootstrapConfigs = $rectorConfigsResolver->provide();
    $rectorContainerFactory = new UpgradeContainerFactory();
    $container = $rectorContainerFactory->createFromBootstrapConfigs($bootstrapConfigs);
} catch (\Throwable $throwable) {
    // for json output
    $argvInput = new ArgvInput();
    $outputFormat = $argvInput->getParameterOption('--' . Option::OUTPUT_FORMAT);
    // report fatal error in json format
    if ($outputFormat === JsonOutputFormatter::NAME) {
        $errors = [];
        do {
            $errors[] = $throwable->getMessage();
        } while ($throwable = $throwable->getPrevious());
        echo Json::encode(['fatal_errors' => $errors]);
    } else {
        // report fatal errors in console format
        $symfonyStyleFactory = new SymfonyStyleFactory(new PrivatesAccessor());
        $symfonyStyle = $symfonyStyleFactory->create();
        do {
            $symfonyStyle->error(\str_replace("\r\n", "\n", $throwable->getMessage()));
        } while ($throwable = $throwable->getPrevious());
    }
    exit(Command::FAILURE);
}
/** @var Application $application */
$application = $container->get(Application::class);
exit($application->run());
