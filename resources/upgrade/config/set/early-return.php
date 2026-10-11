<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all early return rules were moved to code quality set or deprecated
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
