<?php

declare (strict_types=1);
namespace FlamesPrefix202610\Doctrine\Inflector\Rules\French;

use FlamesPrefix202610\Doctrine\Inflector\GenericLanguageInflectorFactory;
use FlamesPrefix202610\Doctrine\Inflector\Rules\Ruleset;
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
