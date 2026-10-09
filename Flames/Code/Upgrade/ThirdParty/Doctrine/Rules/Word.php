<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules;

class Word
{
    public function __construct(private readonly string $word)
    {
    }
    public function getWord(): string
    {
        return $this->word;
    }
}
