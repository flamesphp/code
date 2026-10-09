<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DowngradePhp82\Rector\ArrowFunction\DowngradeArrowFunctionNeverReturnTypeRector;
use Flames\Code\Upgrade\DowngradePhp82\Rector\Class_\DowngradeReadonlyClassRector;
use Flames\Code\Upgrade\DowngradePhp82\Rector\Class_\DowngradeUnionIntersectionRector;
use Flames\Code\Upgrade\DowngradePhp82\Rector\FuncCall\DowngradeIteratorCountToArrayRector;
use Flames\Code\Upgrade\DowngradePhp82\Rector\FunctionLike\DowngradeStandaloneNullTrueFalseReturnTypeRector;
use Flames\Code\Upgrade\DowngradePhp82\Rector\MethodCall\DowngradeReflectionMethodHasPrototypeRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
    $rectorConfig->rules([DowngradeReadonlyClassRector::class, DowngradeStandaloneNullTrueFalseReturnTypeRector::class, DowngradeIteratorCountToArrayRector::class, DowngradeUnionIntersectionRector::class, DowngradeReflectionMethodHasPrototypeRector::class, DowngradeArrowFunctionNeverReturnTypeRector::class]);
};
