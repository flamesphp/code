<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddIntersectionParamToMockObjectParamRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddIntersectionVarToMockObjectPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\AddStubIntersectionVarToStubPropertyRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Flames\Code\Upgrade\PHPUnit110\Rector\ClassMethod\MockObjectArgCreateStubToCreateMockRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\CallLike\CreateStubInCoalesceArgRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Flames\Code\Upgrade\PHPUnit120\Rector\Property\MockObjectVarToStubRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        // stubs over mocks
        CreateStubOverCreateMockArgRector::class,
        CreateStubInCoalesceArgRector::class,
        ExpressionCreateMockToCreateStubRector::class,
        PropertyCreateMockToCreateStubRector::class,
        MockObjectVarToStubRector::class,
        AddIntersectionVarToMockObjectPropertyRector::class,
        AddStubIntersectionVarToStubPropertyRector::class,
        BareCreateMockAssignToDirectUseRector::class,
        // mocks back over stubs, where mock object is required
        MockObjectArgCreateStubToCreateMockRector::class,
        AddIntersectionParamToMockObjectParamRector::class,
    ]);
};
