<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Util;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\PostRector\Rector\PostRectorInterface;
final class UpgradeClassesSorter
{
    /**
     * @param array<class-string<RectorInterface|PostRectorInterface>> $rectorClasses
     * @return array<class-string<RectorInterface>>
     */
    public static function sortAndFilterOutPostRectors(array $rectorClasses): array
    {
        $rectorClasses = array_unique($rectorClasses);
        $mainRectorClasses = array_filter($rectorClasses, fn(string $rectorClass): bool => is_a($rectorClass, RectorInterface::class, \true));
        sort($mainRectorClasses);
        return $mainRectorClasses;
    }
}
