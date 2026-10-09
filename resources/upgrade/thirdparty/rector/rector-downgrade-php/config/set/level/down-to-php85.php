<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Set\ValueObject\DowngradeSetList;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->sets([DowngradeSetList::PHP_86]);
};
