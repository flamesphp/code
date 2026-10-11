<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ValueObject;

use Flames\Code\Upgrade\ValueObject\MethodName;
/**
 * @api designed for public use
 */
final class ObjectMagicMethods
{
    /**
     * @var string[]
     */
    public const array METHOD_NAMES = ['__call', '__callStatic', MethodName::CLONE, MethodName::CONSTRUCT, '__debugInfo', MethodName::DESTRUCT, '__get', MethodName::INVOKE, '__isset', '__serialize', '__set', MethodName::SET_STATE, '__sleep', '__toString', '__unserialize', '__unset', '__wakeup'];
}
