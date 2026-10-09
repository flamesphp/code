<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Util;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
final class StringUtils
{
    public static function isMatch(string $value, string $regex): bool
    {
        $match = Strings::match($value, $regex);
        return $match !== null;
    }
}
