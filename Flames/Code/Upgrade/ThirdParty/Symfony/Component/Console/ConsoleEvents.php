<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console;

use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Event\ConsoleCommandEvent;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Event\ConsoleErrorEvent;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Event\ConsoleSignalEvent;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Event\ConsoleTerminateEvent;
/**
 * Contains all events dispatched by an Application.
 *
 * @author Francesco Levorato <git@flevour.net>
 */
final class ConsoleEvents
{
    /**
     * The COMMAND event allows you to attach listeners before any command is
     * executed by the console. It also allows you to modify the command, input and output
     * before they are handed to the command.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleCommandEvent")
     */
    public const string COMMAND = 'console.command';
    /**
     * The SIGNAL event allows you to perform some actions
     * after the command execution was interrupted.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleSignalEvent")
     */
    public const string SIGNAL = 'console.signal';
    /**
     * The TERMINATE event allows you to attach listeners after a command is
     * executed by the console.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleTerminateEvent")
     */
    public const string TERMINATE = 'console.terminate';
    /**
     * The ERROR event occurs when an uncaught exception or error appears.
     *
     * This event allows you to deal with the exception/error or
     * to modify the thrown exception.
     *
     * @Event("Symfony\Component\Console\Event\ConsoleErrorEvent")
     */
    public const string ERROR = 'console.error';
    /**
     * Event aliases.
     *
     * These aliases can be consumed by RegisterListenersPass.
     */
    public const array ALIASES = [ConsoleCommandEvent::class => self::COMMAND, ConsoleErrorEvent::class => self::ERROR, ConsoleSignalEvent::class => self::SIGNAL, ConsoleTerminateEvent::class => self::TERMINATE];
}
