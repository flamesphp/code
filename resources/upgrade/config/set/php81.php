<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Rules\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\ClosureFromCallableToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Class_\MyCLabsClassToEnumRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Class_\SpatieEnumClassToEnumRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\FuncCall\NullToStrictIntPregSlitFuncCallLimitArgRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\MyCLabsMethodCallToEnumConstRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\MethodCall\SpatieEnumMethodCallToEnumConstRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\New_\MyCLabsConstructorCallToEnumFromRector;
use Flames\Code\Upgrade\Rules\Php81\Rector\Property\ReadOnlyPropertyRector;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        ReturnNeverTypeRector::class,
        MyCLabsClassToEnumRector::class,
        MyCLabsMethodCallToEnumConstRector::class,
        MyCLabsConstructorCallToEnumFromRector::class,
        ReadOnlyPropertyRector::class,
        SpatieEnumClassToEnumRector::class,
        SpatieEnumMethodCallToEnumConstRector::class,
        NullToStrictIntPregSlitFuncCallLimitArgRector::class,
        ArrayToFirstClassCallableRector::class,
        // closure/arrow function
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
        ClosureDelegatingCallToFirstClassCallableRector::class,
        ClosureFromCallableToFirstClassCallableRector::class,
        FunctionFirstClassCallableRector::class,
        RemoveReflectionSetAccessibleCallsRector::class,
    ]);
};
