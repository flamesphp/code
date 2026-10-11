<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine;

interface WordInflector
{
    public function inflect(string $word): string;
}
