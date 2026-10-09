<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all if rules were moved to code quality and coding style sets, or deprecated
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
