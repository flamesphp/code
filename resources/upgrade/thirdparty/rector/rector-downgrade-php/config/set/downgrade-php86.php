<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DowngradePhp86\Rector\FuncCall\DowngradeClampRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_85);
    $rectorConfig->rule(DowngradeClampRector::class);
};
