<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Doctrine\Inflector\Rules\NorwegianBokmal;

use FlamesPrefix202610\Doctrine\Inflector\Rules\Patterns;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Ruleset;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Substitutions;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Transformations;
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
