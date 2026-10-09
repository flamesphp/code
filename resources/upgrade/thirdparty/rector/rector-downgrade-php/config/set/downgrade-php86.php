<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DowngradePhp86\Rector\FuncCall\DowngradeClampRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_85);
    $rectorConfig->rule(DowngradeClampRector::class);
};
