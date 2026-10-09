<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console\Command;

use Flames\Code\Upgrade\ThirdParty\Clue\React\Decoder;
use Flames\Code\Upgrade\ThirdParty\Clue\React\Encoder;
use Flames\Code\Upgrade\ThirdParty\React\EventLoop\StreamSelectLoop;
use Flames\Code\Upgrade\ThirdParty\React\Socket\ConnectionInterface;
use Flames\Code\Upgrade\ThirdParty\React\Socket\TcpConnector;
use Flames\Code\Upgrade\Application\ApplicationFileProcessor;
use Flames\Code\Upgrade\Autoloading\AdditionalAutoloader;
use Flames\Code\Upgrade\Configuration\ConfigurationFactory;
use Flames\Code\Upgrade\Configuration\ConfigurationRuleFilter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Console\ProcessConfigureDecorator;
use Flames\Code\Upgrade\Parallel\Enum\Action;
use Flames\Code\Upgrade\Parallel\Enum\ReactCommand;
use Flames\Code\Upgrade\Parallel\Enum\ReactEvent;
use Flames\Code\Upgrade\Parallel\Enum\StreamFormat;
use Flames\Code\Upgrade\Parallel\ValueObject\Bridge;
use Flames\Code\Upgrade\StaticReflection\DynamicSourceLocatorDecorator;
use Flames\Code\Upgrade\Util\MemoryLimiter;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\InputInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * Inspired at: https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92
 * https://github.com/phpstan/phpstan-src/blob/c471c7b050e0929daf432288770de673b394a983/src/Command/WorkerCommand.php
 *
 * ↓↓↓
 * https://github.com/phpstan/phpstan-src/commit/b84acd2e3eadf66189a64fdbc6dd18ff76323f67#diff-7f625777f1ce5384046df08abffd6c911cfbb1cfc8fcb2bdeaf78f337689e3e2
 */
final class WorkerCommand extends Command
{
    private const string RESULT = 'result';
    public function __construct(private readonly AdditionalAutoloader $additionalAutoloader, private readonly DynamicSourceLocatorDecorator $dynamicSourceLocatorDecorator, private readonly ApplicationFileProcessor $applicationFileProcessor, private readonly MemoryLimiter $memoryLimiter, private readonly ConfigurationFactory $configurationFactory, private readonly ConfigurationRuleFilter $configurationRuleFilter)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('worker');
        $this->setDescription('[INTERNAL] Support for parallel process');
        ProcessConfigureDecorator::decorate($this);
        parent::configure();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = $this->configurationFactory->createFromInput($input);
        $this->memoryLimiter->adjust($configuration);
        $this->configurationRuleFilter->setConfiguration($configuration);
        $streamSelectLoop = new StreamSelectLoop();
        $parallelIdentifier = $configuration->getParallelIdentifier();
        $tcpConnector = new TcpConnector($streamSelectLoop);
        $promise = $tcpConnector->connect('127.0.0.1:' . $configuration->getParallelPort());
        $promise->then(function (ConnectionInterface $connection) use ($parallelIdentifier, $configuration, $input, $output): void {
            $inDecoder = new Decoder($connection, \true, StreamFormat::DEPTH, \JSON_INVALID_UTF8_IGNORE, StreamFormat::MAX_LENGTH);
            $outEncoder = new Encoder($connection, \JSON_INVALID_UTF8_IGNORE);
            $outEncoder->write([ReactCommand::ACTION => Action::HELLO, ReactCommand::IDENTIFIER => $parallelIdentifier]);
            $this->runWorker($outEncoder, $inDecoder, $configuration, $input, $output);
        });
        $streamSelectLoop->run();
        return self::SUCCESS;
    }
    private function runWorker(Encoder $encoder, Decoder $decoder, Configuration $configuration, InputInterface $input, OutputInterface $output): void
    {
        $this->additionalAutoloader->autoloadPaths();
        $this->dynamicSourceLocatorDecorator->addPaths($configuration->getPaths());
        if ($configuration->isDebug()) {
            $preFileCallback = static function (string $filePath) use ($output): void {
                $output->writeln($filePath);
            };
        } else {
            $preFileCallback = null;
        }
        // 1. handle system error
        $handleErrorCallback = static function (Throwable $throwable) use ($encoder): void {
            $systemError = new SystemError($throwable->getMessage(), $throwable->getFile(), $throwable->getLine());
            $encoder->write([ReactCommand::ACTION => Action::RESULT, self::RESULT => [Bridge::SYSTEM_ERRORS => [$systemError], Bridge::FILES_COUNT => 0, Bridge::SYSTEM_ERRORS_COUNT => 1]]);
            $encoder->end();
        };
        $encoder->on(ReactEvent::ERROR, $handleErrorCallback);
        // 2. collect diffs + errors from file processor
        $decoder->on(ReactEvent::DATA, function (array $json) use ($preFileCallback, $encoder, $configuration, $input): void {
            $action = $json[ReactCommand::ACTION];
            if ($action !== Action::MAIN) {
                return;
            }
            /** @var string[] $filePaths */
            $filePaths = $json[Bridge::FILES] ?? [];
            Assert::notEmpty($filePaths);
            $processResult = $this->applicationFileProcessor->processFiles($filePaths, $configuration, $preFileCallback);
            /**
             * this invokes all listeners listening $decoder->on(...) @see \Flames\Code\Upgrade\Parallel\Enum\ReactEvent::DATA
             */
            $encoder->write([ReactCommand::ACTION => Action::RESULT, self::RESULT => [Bridge::FILE_DIFFS => $processResult->getFileDiffs($input->getOption(Option::OUTPUT_FORMAT) !== 'json'), Bridge::FILES_COUNT => count($filePaths), Bridge::SYSTEM_ERRORS => $processResult->getSystemErrors(), Bridge::SYSTEM_ERRORS_COUNT => count($processResult->getSystemErrors()), Bridge::TOTAL_CHANGED => $processResult->getTotalChanged(), Bridge::USED_SKIPS => $processResult->getUsedSkips()]]);
        });
        $decoder->on(ReactEvent::ERROR, $handleErrorCallback);
    }
}
