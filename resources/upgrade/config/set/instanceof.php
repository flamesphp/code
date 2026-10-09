<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all instanceof rules were moved to code quality and type declaration sets
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
