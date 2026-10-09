<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DowngradePhp83\Rector\Class_\DowngradeReadonlyAnonymousClassRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConst\DowngradeTypedClassConstRector;
use Flames\Code\Upgrade\DowngradePhp83\Rector\ClassConstFetch\DowngradeDynamicClassConstFetchRector;
use Flames\Code\Upgrade\DowngradePhp83\Rector\FuncCall\DowngradeJsonValidateRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_82);
    $rectorConfig->rules([DowngradeTypedClassConstRector::class, DowngradeReadonlyAnonymousClassRector::class, DowngradeDynamicClassConstFetchRector::class, DowngradeJsonValidateRector::class]);
};
