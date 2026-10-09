<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console;

use Flames\Code\Upgrade\ThirdParty\Composer\XdebugHandler;
use Flames\Code\Upgrade\Application\VersionResolver;
use Flames\Code\Upgrade\ChangesReporting\Output\ConsoleOutputFormatter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Util\Reflection\PrivatesAccessor;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Application;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputDefinition;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputOption;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\OutputInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Style\SymfonyStyle;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final class ConsoleApplication extends Application
{
    private const string NAME = 'Rector';
    /**
     * @param Command[] $commands
     */
    public function __construct(array $commands, private readonly SymfonyStyle $symfonyStyle)
    {
        parent::__construct(self::NAME, VersionResolver::PACKAGE_VERSION);
        Assert::notEmpty($commands);
        Assert::allIsInstanceOf($commands, Command::class);
        $this->addCommands($commands);
        // run this command, if no command name is provided
        $this->setDefaultCommand('process');
    }
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        // support "-v" as an alias for "--version", as "verbose" option is removed
        if ($input->hasParameterOption('-v', \true)) {
            $output->writeln($this->getLongVersion());
            return \Flames\Code\Upgrade\Console\ExitCode::SUCCESS;
        }
        $this->enableXdebug($input);
        $shouldFollowByNewline = \false;
        // skip in this case, since generate content must be clear from meta-info
        if ($this->shouldPrintMetaInformation($input)) {
            $output->writeln($this->getLongVersion());
            $shouldFollowByNewline = \true;
        }
        if ($shouldFollowByNewline) {
            $output->write(\PHP_EOL);
        }
        $commandName = $input->getFirstArgument();
        if ($commandName === null) {
            return parent::doRun($input, $output);
        }
        // if paths exist or if the command name is not the first argument but with --option, eg:
        // bin/code-upgrade src
        // bin/code-upgrade --only "RemovePhpVersionIdCheckRector"
        // file_exists() can check directory and file
        if (file_exists($commandName) || isset($_SERVER['argv'][1]) && $commandName !== $_SERVER['argv'][1] && $input->hasParameterOption($_SERVER['argv'][1])) {
            // prepend command name if implicit
            $privatesAccessor = new PrivatesAccessor();
            $tokens = $privatesAccessor->getPrivateProperty($input, 'tokens');
            $tokens = array_merge(['process'], $tokens);
            $privatesAccessor->setPrivateProperty($input, 'tokens', $tokens);
        } elseif (!$this->has($commandName)) {
            $this->symfonyStyle->error(sprintf('The following given path does not match any files or directories: %s%s', "\n\n - ", $commandName));
            return \Flames\Code\Upgrade\Console\ExitCode::FAILURE;
        }
        return parent::doRun($input, $output);
    }
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $defaultInputDefinition = parent::getDefaultInputDefinition();
        $this->removeUnusedOptions($defaultInputDefinition);
        $this->addCustomOptions($defaultInputDefinition);
        return $defaultInputDefinition;
    }
    private function shouldPrintMetaInformation(InputInterface $input): bool
    {
        $hasNoArguments = $input->getFirstArgument() === null;
        if ($hasNoArguments) {
            return \false;
        }
        $hasVersionOption = $input->hasParameterOption('--version');
        if ($hasVersionOption) {
            return \false;
        }
        $outputFormat = $input->getParameterOption(['-o', '--output-format']);
        return $outputFormat === ConsoleOutputFormatter::NAME;
    }
    private function removeUnusedOptions(InputDefinition $inputDefinition): void
    {
        $options = $inputDefinition->getOptions();
        unset($options['quiet'], $options['verbose'], $options['no-interaction']);
        $inputDefinition->setOptions($options);
    }
    private function addCustomOptions(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addOption(new InputOption(Option::CONFIG, 'c', InputOption::VALUE_REQUIRED, 'Path to config file'));
        $inputDefinition->addOption(new InputOption(Option::DEBUG, null, InputOption::VALUE_NONE, 'Enable debug verbosity'));
        $inputDefinition->addOption(new InputOption(Option::XDEBUG, null, InputOption::VALUE_NONE, 'Allow running xdebug'));
        $inputDefinition->addOption(new InputOption(Option::CLEAR_CACHE, null, InputOption::VALUE_NONE, 'Clear cache before starting the execution of the command'));
    }
    private function enableXdebug(InputInterface $input): void
    {
        $isXdebugAllowed = $input->hasParameterOption('--xdebug');
        if (!$isXdebugAllowed) {
            $xdebugHandler = new XdebugHandler('rector');
            $xdebugHandler->setPersistent();
            $xdebugHandler->check();
            unset($xdebugHandler);
        }
    }
}
