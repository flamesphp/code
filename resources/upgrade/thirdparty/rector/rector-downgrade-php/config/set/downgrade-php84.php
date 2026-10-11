<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\DowngradePhp84\Rector\Class_\DowngradeFinalPropertyRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\ClassMethod\DowngradeDeprecatedAttributeRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\Expression\DowngradeArrayAllRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\Expression\DowngradeArrayAnyRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\Expression\DowngradeArrayFindKeyRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\Expression\DowngradeArrayFindRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall\DowngradeExitNamedArgumentRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall\DowngradeRoundingModeEnumRector;
use Flames\Code\Upgrade\DowngradePhp84\Rector\MethodCall\DowngradeNewMethodCallWithoutParenthesesRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_83);
    $rectorConfig->rules([DowngradeNewMethodCallWithoutParenthesesRector::class, DowngradeExitNamedArgumentRector::class, DowngradeRoundingModeEnumRector::class, DowngradeArrayAllRector::class, DowngradeArrayAnyRector::class, DowngradeArrayFindRector::class, DowngradeArrayFindKeyRector::class, DowngradeDeprecatedAttributeRector::class, DowngradeFinalPropertyRector::class]);
};
