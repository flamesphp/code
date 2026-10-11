<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\ClassMethod\DowngradeContravariantArgumentTypeRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\ClassMethod\DowngradeCovariantReturnTypeRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\FuncCall\DowngradeArrayMergeCallWithoutArgumentsRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\FuncCall\DowngradeProcOpenArrayCommandArgRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\FuncCall\DowngradeStripTagsCallWithArrayRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\Identical\DowngradeFreadFwriteFalsyToNegationRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\Interface_\DowngradePreviouslyImplementedInterfaceRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\LNumber\DowngradeNumericLiteralSeparatorRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\MethodCall\DowngradeReflectionGetTypeRector;
use Flames\Code\Upgrade\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_73);
    $rectorConfig->rules([
        DowngradeTypedPropertyRector::class,
        ArrowFunctionToAnonymousFunctionRector::class,
        DowngradeCovariantReturnTypeRector::class,
        DowngradeContravariantArgumentTypeRector::class,
        DowngradeNullCoalescingOperatorRector::class,
        DowngradeNumericLiteralSeparatorRector::class,
        DowngradeStripTagsCallWithArrayRector::class,
        // DowngradeArraySpreadRector::class,
        // already handled in PHP 8.1 set
        DowngradeArrayMergeCallWithoutArgumentsRector::class,
        DowngradeFreadFwriteFalsyToNegationRector::class,
        DowngradePreviouslyImplementedInterfaceRector::class,
        DowngradeReflectionGetTypeRector::class,
        DowngradeProcOpenArrayCommandArgRector::class,
    ]);
};
