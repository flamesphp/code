<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php72\Rector\Assign\ListEachRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\Assign\ReplaceEachAssignmentWithKeyCurrentRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\GetClassOnNullRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\StringifyDefineRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\StringsAssertNakedRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\Unset_\UnsetCastRector;
use Flames\Code\Upgrade\Rules\Php72\Rector\While_\WhileEachToForeachRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameFunctionRector::class, [
        # and imagewbmp
        'jpeg2wbmp' => 'imagecreatefromjpeg',
        # or imagewbmp
        'png2wbmp' => 'imagecreatefrompng',
        # migration72.deprecated.gmp_random-function
        # http://php.net/manual/en/migration72.deprecated.php
        # or gmp_random_range
        'gmp_random' => 'gmp_random_bits',
        'read_exif_data' => 'exif_read_data',
    ]);
    $rectorConfig->rules([GetClassOnNullRector::class, ParseStrWithResultArgumentRector::class, StringsAssertNakedRector::class, CreateFunctionToAnonymousFunctionRector::class, StringifyDefineRector::class, WhileEachToForeachRector::class, ListEachRector::class, ReplaceEachAssignmentWithKeyCurrentRector::class, UnsetCastRector::class]);
};
