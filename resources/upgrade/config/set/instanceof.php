<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all instanceof rules were moved to code quality and type declaration sets
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
