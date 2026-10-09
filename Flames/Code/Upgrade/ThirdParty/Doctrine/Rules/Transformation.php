<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\Rules;

use Flames\Code\Upgrade\ThirdParty\Doctrine\WordInflector;
use function preg_replace;
final readonly class Transformation implements WordInflector
{
    public function __construct(private Pattern $pattern, private string $replacement)
    {
    }
    public function getPattern(): Pattern
    {
        return $this->pattern;
    }
    public function getReplacement(): string
    {
        return $this->replacement;
    }
    public function inflect(string $word): string
    {
        return (string) preg_replace($this->pattern->getRegex(), $this->replacement, $word);
    }
}
