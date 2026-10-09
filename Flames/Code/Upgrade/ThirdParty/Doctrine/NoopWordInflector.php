<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine;

class NoopWordInflector implements WordInflector
{
    public function inflect(string $word): string
    {
        return $word;
    }
}
