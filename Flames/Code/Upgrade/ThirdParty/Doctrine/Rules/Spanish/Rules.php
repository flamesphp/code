<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Spanish;

use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Patterns;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Ruleset;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Substitutions;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset(): Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), new Substitutions(...Inflectible::getIrregular())->getFlippedSubstitutions());
    }
    public static function getPluralRuleset(): Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
