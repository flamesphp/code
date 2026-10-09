<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Esperanto;

use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Pattern;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Substitution;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Transformation;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Word;
class Inflectible
{
    /** @return Transformation[] */
    public static function getSingular(): iterable
    {
        yield new Transformation(new Pattern('oj$'), 'o');
    }
    /** @return Transformation[] */
    public static function getPlural(): iterable
    {
        yield new Transformation(new Pattern('o$'), 'oj');
    }
    /** @return Substitution[] */
    public static function getIrregular(): iterable
    {
        yield new Substitution(new Word(''), new Word(''));
    }
}
