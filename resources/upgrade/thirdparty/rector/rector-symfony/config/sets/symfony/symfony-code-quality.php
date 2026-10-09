<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\BinaryOp\ResponseStatusCodeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\EventListenerToEventSubscriberRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\EventSubscriberMethodReturnVoidRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\LoadValidatorMetadataToAttributeRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ParamTypeFromRouteRequiredRegexRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\RemoveUnusedRequestParamRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameResponseCodeWithDebugContentsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\StringCastDebugResponseRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        EventListenerToEventSubscriberRector::class,
        ResponseReturnTypeControllerActionRector::class,
        EventSubscriberMethodReturnVoidRector::class,
        // int and string literals to const fetches
        ResponseStatusCodeRector::class,
        RemoveUnusedRequestParamRector::class,
        ParamTypeFromRouteRequiredRegexRector::class,
        // controller
        LoadValidatorMetadataToAttributeRector::class,
        // tests
        AssertSameResponseCodeWithDebugContentsRector::class,
        StringCastDebugResponseRector::class,
    ]);
};
