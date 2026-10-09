<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all early return rules were moved to code quality set or deprecated
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
