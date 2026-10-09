<?php

/*
 * This file is part of the Fidry CPUCounter Config package.
 *
 * (c) Théo FIDRY <theo.fidry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace FlamesPrefix202610\Fidry\CpuCoreCounter\Finder;

use function sprintf;
/**
 * This finder returns whatever value you gave to it. This is useful for testing
 * or as a fallback to avoid to catch the NumberOfCpuCoreNotFound exception.
 */
final readonly class DummyCpuCoreFinder implements CpuCoreFinder
{
    public function diagnose(): string
    {
        return sprintf('Will return "%d".', $this->count);
    }
    /**
     * @param positive-int $count
     */
    public function __construct(private int $count)
    {
    }
    /** @phpstan-ignore return.unusedType */
    public function find(): ?int
    {
        return $this->count;
    }
    public function toString(): string
    {
        return sprintf('DummyCpuCoreFinder(value=%d)', $this->count);
    }
}
