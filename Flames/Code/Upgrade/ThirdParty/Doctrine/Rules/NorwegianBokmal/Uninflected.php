<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\NorwegianBokmal;

use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Pattern;
final class Uninflected
{
    /** @return Pattern[] */
    public static function getSingular(): iterable
    {
        yield from self::getDefault();
    }
    /** @return Pattern[] */
    public static function getPlural(): iterable
    {
        yield from self::getDefault();
    }
    /** @return Pattern[] */
    private static function getDefault(): iterable
    {
        yield new Pattern('barn');
        yield new Pattern('fjell');
        yield new Pattern('hus');
    }
}
