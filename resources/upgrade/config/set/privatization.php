<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Privatization\Rector\ClassConst\PrivatizeFinalClassConstantRector;
use Flames\Code\Upgrade\Rules\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Flames\Code\Upgrade\Rules\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Flames\Code\Upgrade\Rules\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([PrivatizeLocalGetterToPropertyRector::class, PrivatizeFinalClassPropertyRector::class, PrivatizeFinalClassMethodRector::class, PrivatizeFinalClassConstantRector::class]);
};
