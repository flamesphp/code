<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
// note: all if rules were moved to code quality and coding style sets, or deprecated
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([]);
};
