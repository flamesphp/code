<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Configs\Rector\Closure\FromServicePublicToDefaultsPublicRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\MergeServiceNameTypeRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\RemoveConstructorAutowireServiceRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\ServiceArgsToServiceNamedArgRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\ServiceSetStringNameToClassNameRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\ServiceSettersToSettersAutodiscoveryRector;
use Flames\Code\Upgrade\Configs\Rector\Closure\ServiceTagsToDefaultsAutoconfigureRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([MergeServiceNameTypeRector::class, ServiceArgsToServiceNamedArgRector::class, ServiceSetStringNameToClassNameRector::class, ServiceSettersToSettersAutodiscoveryRector::class, ServiceTagsToDefaultsAutoconfigureRector::class, RemoveConstructorAutowireServiceRector::class, FromServicePublicToDefaultsPublicRector::class]);
};
