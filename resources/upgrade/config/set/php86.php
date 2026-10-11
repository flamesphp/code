<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php86\Rector\Class_\ConstructorReadonlyAssignToDefaultRector;
use Flames\Code\Upgrade\Rules\Php86\Rector\FuncCall\MinMaxToClampRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([MinMaxToClampRector::class, ConstructorReadonlyAssignToDefaultRector::class]);
};
