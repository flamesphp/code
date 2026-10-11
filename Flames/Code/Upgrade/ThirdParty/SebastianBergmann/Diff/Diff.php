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
 * @template-implements IteratorAggregate<int, Chunk>
 */
final class Diff implements IteratorAggregate
{
    /**
     * @param non-empty-string $from
     * @param non-empty-string $to
     * @param list<Chunk>      $chunks
     */
    public function __construct(private readonly string $from, private readonly string $to, private array $chunks = [])
    {
    }
    /**
     * @return non-empty-string
     */
    public function from(): string
    {
        return $this->from;
    }
    /**
     * @return non-empty-string
     */
    public function to(): string
    {
        return $this->to;
    }
    /**
     * @return list<Chunk>
     */
    public function chunks(): array
    {
        return $this->chunks;
    }
    /**
     * @param list<Chunk> $chunks
     */
    public function setChunks(array $chunks): void
    {
        $this->chunks = $chunks;
    }
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->chunks);
    }
}
