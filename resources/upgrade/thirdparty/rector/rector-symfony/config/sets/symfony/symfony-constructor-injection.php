<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DependencyInjection\Rector\Class_\CommandGetByTypeToConstructorInjectionRector;
use Flames\Code\Upgrade\DependencyInjection\Rector\Class_\ControllerGetByTypeToConstructorInjectionRector;
use Flames\Code\Upgrade\DependencyInjection\Rector\Class_\GetBySymfonyStringToConstructorInjectionRector;
use Flames\Code\Upgrade\DependencyInjection\Rector\MethodCall\GetToConstructorInjectionRector;
use Flames\Code\Upgrade\DependencyInjection\Rector\Closure\ContainerGetNameToTypeInTestsRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        // modern step-by-step narrow approach
        ControllerGetByTypeToConstructorInjectionRector::class,
        CommandGetByTypeToConstructorInjectionRector::class,
        GetBySymfonyStringToConstructorInjectionRector::class,
        // legacy rules that require container fetch
        ContainerGetNameToTypeInTestsRector::class,
        GetToConstructorInjectionRector::class,
    ]);
};
