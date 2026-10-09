<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
final class ArraySpreadAnalyzer
{
    public function isArrayWithUnpack(Array_ $array): bool
    {
        // Check that any item in the array is the spread
        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }
            if ($item->unpack) {
                return \true;
            }
        }
        return \false;
    }
}
