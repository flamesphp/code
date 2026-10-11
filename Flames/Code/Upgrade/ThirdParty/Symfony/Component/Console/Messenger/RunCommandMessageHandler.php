<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Messenger;

use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Application;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Exception\RunCommandFailedException;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Input\StringInput;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\BufferedOutput;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Messenger\Exception\RecoverableExceptionInterface;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final readonly class RunCommandMessageHandler
{
    public function __construct(private Application $application)
    {
    }
    public function __invoke(RunCommandMessage $message): RunCommandContext
    {
        $input = new StringInput($message->input);
        $output = new BufferedOutput();
        $originalCatchExceptions = $this->application->areExceptionsCaught();
        $this->application->setCatchExceptions($message->catchExceptions);
        try {
            $exitCode = $this->application->run($input, $output);
        } catch (UnrecoverableExceptionInterface|RecoverableExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RunCommandFailedException($e, new RunCommandContext($message, Command::FAILURE, $output->fetch()));
        } finally {
            $this->application->setCatchExceptions($originalCatchExceptions);
        }
        if ($message->throwOnFailure && Command::SUCCESS !== $exitCode) {
            throw new RunCommandFailedException(\sprintf('Command "%s" exited with code "%s".', $message->input, $exitCode), new RunCommandContext($message, $exitCode, $output->fetch()));
        }
        return new RunCommandContext($message, $exitCode, $output->fetch());
    }
}
