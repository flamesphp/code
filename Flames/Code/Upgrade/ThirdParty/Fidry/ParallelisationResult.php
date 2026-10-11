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
namespace Flames\Code\Upgrade\ThirdParty\Fidry;

/**
 * @readonly
 */
final class ParallelisationResult
{
    /**
     * @param positive-int|0    $passedReservedCpus
     * @param non-zero-int|null $passedCountLimit
     * @param non-zero-int|null $correctedCountLimit
     * @param positive-int      $totalCoresCount
     * @param positive-int      $availableCpus
     */
    public function __construct(public int $passedReservedCpus, public ?int $passedCountLimit, public ?float $passedLoadLimit, public ?float $passedSystemLoadAverage, public ?int $correctedCountLimit, public ?float $correctedSystemLoadAverage, public int $totalCoresCount, public int $availableCpus)
    {
    }
}
