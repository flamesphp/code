<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp73\Rector\ConstFetch\DowngradePhp73JsonConstRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\FuncCall\DowngradeArrayKeyFirstLastRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\FuncCall\DowngradeIsCountableRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\FuncCall\SetCookieOptionsArrayToArgumentsRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\List_\DowngradeListReferenceAssignmentRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\String_\DowngradeFlexibleHeredocSyntaxRector;
use Flames\Code\Upgrade\DowngradePhp73\Rector\Unset_\DowngradeTrailingCommasInUnsetRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_72);
    $rectorConfig->rules([DowngradeFlexibleHeredocSyntaxRector::class, DowngradeListReferenceAssignmentRector::class, DowngradeTrailingCommasInFunctionCallsRector::class, DowngradeArrayKeyFirstLastRector::class, SetCookieOptionsArrayToArgumentsRector::class, DowngradeIsCountableRector::class, DowngradePhp73JsonConstRector::class, DowngradeTrailingCommasInUnsetRector::class]);
};
