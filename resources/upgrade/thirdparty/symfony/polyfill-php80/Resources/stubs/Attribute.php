<?php

namespace FlamesPrefix202610;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Attribute
{
    public const int TARGET_CLASS = 1;
    public const int TARGET_FUNCTION = 2;
    public const int TARGET_METHOD = 4;
    public const int TARGET_PROPERTY = 8;
    public const int TARGET_CLASS_CONSTANT = 16;
    public const int TARGET_PARAMETER = 32;
    public const int TARGET_ALL = 63;
    public const int IS_REPEATABLE = 64;
    public function __construct(public int $flags = self::TARGET_ALL)
    {
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
\class_alias(\FlamesPrefix202610\Attribute::class, 'Attribute', \false);
