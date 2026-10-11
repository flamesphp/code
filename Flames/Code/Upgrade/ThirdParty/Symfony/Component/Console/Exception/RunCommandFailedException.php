<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Exception;

use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Messenger\RunCommandContext;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RunCommandFailedException extends RuntimeException
{
    /**
     * @param \Throwable|string $exception
     */
    public function __construct($exception, /**
     * @readonly
     */
    public RunCommandContext $context)
    {
        parent::__construct($exception instanceof \Throwable ? $exception->getMessage() : $exception, $exception instanceof \Throwable && \is_int($exception->getCode()) ? $exception->getCode() : 0, $exception instanceof \Throwable ? $exception : null);
    }
}
