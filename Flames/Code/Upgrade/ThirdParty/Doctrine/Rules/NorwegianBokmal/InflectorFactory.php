<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\NorwegianBokmal;

use Flames\Code\Upgrade\ThirdParty\Doctrine\GenericLanguageInflectorFactory;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
