<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Rules\Carbon\Rector\FuncCall\DateFuncCallToCarbonRector;
use Flames\Code\Upgrade\Rules\Carbon\Rector\FuncCall\TimeFuncCallToCarbonRector;
use Flames\Code\Upgrade\Rules\Carbon\Rector\MethodCall\DateTimeMethodCallToCarbonRector;
use Flames\Code\Upgrade\Rules\Carbon\Rector\New_\DateTimeInstanceToCarbonRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([DateFuncCallToCarbonRector::class, DateTimeInstanceToCarbonRector::class, DateTimeMethodCallToCarbonRector::class, TimeFuncCallToCarbonRector::class]);
};
