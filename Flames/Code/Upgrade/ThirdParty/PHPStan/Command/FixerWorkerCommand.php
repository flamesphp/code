<?php

declare (strict_types=1);
namespace PHPStan\Command;

use Override;
use PHPStan\File\PathNotFoundException;
use PHPStan\ShouldNotHappenException;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputArgument;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputOption;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\OutputInterface;
use function is_array;
use function is_bool;
use function is_string;
final class FixerWorkerCommand extends Command
{
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    private const NAME = 'fixer:worker';
    /**
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(array $composerAutoloaderProjectPaths)
    {
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        parent::__construct();
    }
    #[Override]
    protected function configure(): void
    {
        $this->setName(self::NAME)->setDescription('(Internal) Support for PHPStan Pro.')->setDefinition([new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'), new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'), new InputOption(\PHPStan\Command\AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'), new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'), new InputOption('memory-limit', \null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'), new InputOption('xdebug', \null, InputOption::VALUE_NONE, 'Allow running with Xdebug for debugging purposes'), new InputOption('server-port', \null, InputOption::VALUE_REQUIRED, 'Server port for FixerApplication')])->setHidden(\true);
    }
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $input->getArgument('paths');
        $memoryLimit = $input->getOption('memory-limit');
        $autoloadFile = $input->getOption('autoload-file');
        $configuration = $input->getOption('configuration');
        $level = $input->getOption(\PHPStan\Command\AnalyseCommand::OPTION_LEVEL);
        $allowXdebug = $input->getOption('xdebug');
        $serverPort = $input->getOption('server-port');
        if (!is_array($paths) || !is_string($memoryLimit) && $memoryLimit !== null || !is_string($autoloadFile) && $autoloadFile !== null || !is_string($configuration) && $configuration !== null || !is_string($level) && $level !== null || !is_bool($allowXdebug) || !is_string($serverPort)) {
            throw new ShouldNotHappenException();
        }
        try {
            $inceptionResult = \PHPStan\Command\CommandHelper::begin($input, $output, $paths, $memoryLimit, $autoloadFile, $this->composerAutoloaderProjectPaths, $configuration, null, $level, $allowXdebug, \false, null, null, \false);
        } catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
            return 1;
        }
        $container = $inceptionResult->container;
        try {
            [$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
        } catch (PathNotFoundException|\PHPStan\Command\InceptionNotSuccessfulException $e) {
            throw new ShouldNotHappenException();
        }
        // Everything after the boot lives in FixerWorkerRunner so a
        // pcntl_fork()-ed child can reuse it without re-booting (see
        // FixerApplication).
        $fixerWorkerRunner = $container->getByType(\PHPStan\Command\FixerWorkerRunner::class);
        return $fixerWorkerRunner->run($inceptionResult->errorOutput, $inceptionFiles, $isOnlyFiles, $inceptionResult->projectConfigArray, $configuration, (int) $serverPort, $input);
    }
}
