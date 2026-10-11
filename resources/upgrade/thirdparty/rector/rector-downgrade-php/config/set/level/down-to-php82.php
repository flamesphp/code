<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Set\ValueObject\DowngradeLevelSetList;
use Flames\Code\Upgrade\Set\ValueObject\DowngradeSetList;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->sets([DowngradeLevelSetList::DOWN_TO_PHP_83, DowngradeSetList::PHP_83]);
};
