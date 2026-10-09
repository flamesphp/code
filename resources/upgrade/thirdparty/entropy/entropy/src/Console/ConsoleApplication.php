<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Entropy\Console;

use FlamesPrefix202610\Entropy\Attribute\RelatedTest;
use FlamesPrefix202610\Entropy\Console\Contract\CommandInterface;
use FlamesPrefix202610\Entropy\Console\Enum\ExitCode;
use FlamesPrefix202610\Entropy\Console\Input\InputParser;
use FlamesPrefix202610\Entropy\Console\Mapper\CLIRequestMapper;
use FlamesPrefix202610\Entropy\Console\Output\CommandHelpFactory;
use FlamesPrefix202610\Entropy\Console\Output\HelpPrinter;
use FlamesPrefix202610\Entropy\Console\Output\OutputPrinter;
use FlamesPrefix202610\Entropy\Tests\Console\ConsoleApplication\ConsoleApplicationTest;
use Throwable;
final readonly class ConsoleApplication
{
    public function __construct(private HelpPrinter $helpPrinter, private OutputPrinter $outputPrinter, private CommandHelpFactory $commandHelpFactory, private InputParser $inputParser, private CommandRegistry $commandRegistry, private CLIRequestMapper $cliRequestMapper)
    {
    }
    /**
     * @param mixed[] $argv
     * @return ExitCode::*
     */
    public function run(array $argv): int
    {
        $cliRequest = $this->inputParser->parse($argv);
        $commandName = $cliRequest->getCommandName();
        // no command name given - fall back to the default command, or show help
        if ($commandName === null) {
            $defaultCommand = $this->commandRegistry->getDefault();
            $wantsHelp = array_intersect(['h', 'help'], array_keys($cliRequest->getOptions())) !== [];
            if (!$defaultCommand instanceof CommandInterface || $wantsHelp) {
                $this->helpPrinter->print();
                return ExitCode::SUCCESS;
            }
            $commandName = $defaultCommand->getName();
        }
        if (!$this->commandRegistry->has($commandName)) {
            $defaultCommand = $this->commandRegistry->getDefault();
            // with a default command, an unknown leading token is its first argument (e.g. "ecs src")
            if (!$defaultCommand instanceof CommandInterface) {
                fwrite(\STDERR, sprintf("Unknown command: %s\n\n", $commandName));
                $this->helpPrinter->print();
                return ExitCode::INVALID_COMMAND;
            }
            $cliRequest = $cliRequest->withCommandNameAndPrependedArgument($defaultCommand->getName(), $commandName);
            $commandName = $defaultCommand->getName();
        }
        try {
            $command = $this->commandRegistry->get($commandName);
            if ($cliRequest->isCommandHelp()) {
                // build command help here :)
                $commandHelp = $this->commandHelpFactory->build($command);
                $this->outputPrinter->writeln($commandHelp);
                return ExitCode::SUCCESS;
            }
            $runArguments = $this->cliRequestMapper->resolveArguments($command, $cliRequest);
            return $command->run(...$runArguments);
        } catch (Throwable $throwable) {
            $this->outputPrinter->redBackground('Run failed: ' . $throwable->getMessage());
            $this->outputPrinter->newline();
            $this->outputPrinter->writeln($throwable->getTraceAsString());
            return ExitCode::ERROR;
        }
    }
}
