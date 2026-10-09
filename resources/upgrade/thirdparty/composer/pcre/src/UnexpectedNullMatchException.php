<?php

/*
 * This file is part of composer/pcre.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
namespace FlamesPrefix202610\Composer\Pcre;

class UnexpectedNullMatchException extends PcreException
{
    public static function fromFunction($function, $pattern): never
    {
        throw new \LogicException('fromFunction should not be called on ' . self::class . ', use ' . PcreException::class);
    }
}
