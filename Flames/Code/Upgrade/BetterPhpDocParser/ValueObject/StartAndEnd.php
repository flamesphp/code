<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject;

use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
final readonly class StartAndEnd
{
    public function __construct(private int $start, private int $end)
    {
        if ($this->end < $this->start) {
            throw new ShouldNotHappenException();
        }
    }
    public function getStart(): int
    {
        return $this->start;
    }
    public function getEnd(): int
    {
        return $this->end;
    }
}
