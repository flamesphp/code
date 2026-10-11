<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\DataProvider;

use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
final class CurrentTokenIteratorProvider
{
    private ?BetterTokenIterator $betterTokenIterator = null;
    public function setBetterTokenIterator(BetterTokenIterator $betterTokenIterator): void
    {
        $this->betterTokenIterator = $betterTokenIterator;
    }
    public function provide(): BetterTokenIterator
    {
        if (!$this->betterTokenIterator instanceof BetterTokenIterator) {
            throw new ShouldNotHappenException();
        }
        return $this->betterTokenIterator;
    }
}
