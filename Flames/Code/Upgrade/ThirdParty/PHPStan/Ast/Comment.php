<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast;

use function trim;
class Comment
{
    public function __construct(public string $text, public int $startLine = -1, public int $startIndex = -1)
    {
    }
    public function getReformattedText(): string
    {
        return trim($this->text);
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['text'], $properties['startLine'], $properties['startIndex']);
    }
}
