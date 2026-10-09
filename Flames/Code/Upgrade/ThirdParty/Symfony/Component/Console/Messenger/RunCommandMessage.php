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

use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Exception\RunCommandFailedException;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class RunCommandMessage implements \Stringable
{
    /**
     * @param bool $throwOnFailure  If the command has a non-zero exit code, throw {@see RunCommandFailedException}
     * @param bool $catchExceptions @see Application::setCatchExceptions()
     */
    public function __construct(
        /**
         * @readonly
         */
        public string $input,
        /**
         * @readonly
         */
        public bool $throwOnFailure = \true,
        /**
         * @readonly
         */
        public bool $catchExceptions = \false
    )
    {
    }
    public function __toString(): string
    {
        return $this->input;
    }
}
