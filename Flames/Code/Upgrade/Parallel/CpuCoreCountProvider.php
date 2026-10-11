<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Parallel;

use Flames\Code\Upgrade\ThirdParty\Fidry\CpuCoreCounter;
use Flames\Code\Upgrade\ThirdParty\Fidry\NumberOfCpuCoreNotFound;
final class CpuCoreCountProvider
{
    private const int DEFAULT_CORE_COUNT = 2;
    public function provide(): int
    {
        try {
            return new CpuCoreCounter()->getCount();
        } catch (NumberOfCpuCoreNotFound) {
            return self::DEFAULT_CORE_COUNT;
        }
    }
}
