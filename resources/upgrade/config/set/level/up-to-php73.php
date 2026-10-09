<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Set\ValueObject\LevelSetList;
use Flames\Code\Upgrade\Set\ValueObject\SetList;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->sets([SetList::PHP_73, LevelSetList::UP_TO_PHP_72]);
};
