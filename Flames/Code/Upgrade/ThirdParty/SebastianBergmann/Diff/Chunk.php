<?php

declare (strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Flames\Code\Upgrade\ThirdParty\SebastianBergmann\Diff;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
/**
 * @template-implements IteratorAggregate<int, Line>
 */
final class Chunk implements IteratorAggregate
{
    /**
     * @param list<Line> $lines
     */
    public function __construct(private readonly int $start = 0, private readonly int $startRange = 1, private readonly int $end = 0, private readonly int $endRange = 1, private array $lines = [])
    {
    }
    public function start(): int
    {
        return $this->start;
    }
    public function startRange(): int
    {
        return $this->startRange;
    }
    public function end(): int
    {
        return $this->end;
    }
    public function endRange(): int
    {
        return $this->endRange;
    }
    /**
     * @return list<Line>
     */
    public function lines(): array
    {
        return $this->lines;
    }
    /**
     * @param list<Line> $lines
     */
    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }
}
