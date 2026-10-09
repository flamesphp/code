<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules;

class Ruleset
{
    public function __construct(private readonly Transformations $regular, private readonly Patterns $uninflected, private readonly Substitutions $irregular)
    {
    }
    public function getRegular(): Transformations
    {
        return $this->regular;
    }
    public function getUninflected(): Patterns
    {
        return $this->uninflected;
    }
    public function getIrregular(): Substitutions
    {
        return $this->irregular;
    }
}
