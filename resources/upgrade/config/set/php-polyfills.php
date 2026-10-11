<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php73\Rector\BooleanOr\IsCountableRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Flames\Code\Upgrade\Rules\Php73\Rector\FuncCall\ArrayKeysToArrayKeyFirstLastRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Identical\StrEndsWithRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Identical\StrStartsWithRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\NotIdentical\MbStrContainsRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\NotIdentical\StrContainsRector;
use Flames\Code\Upgrade\Rules\Php80\Rector\Ternary\GetDebugTypeRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\BooleanAnd\JsonValidateRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindKeyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindRector;
// @note longer rule registration must be used here, to separate from withRules() from root code-upgrade.php
// these rules can be used ahead of PHP version,
// as long composer.json includes particular symfony/php-polyfill package
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        ArrayKeyFirstLastRector::class,
        ArrayKeysToArrayKeyFirstLastRector::class,
        IsCountableRector::class,
        GetDebugTypeRector::class,
        StrStartsWithRector::class,
        StrEndsWithRector::class,
        StrContainsRector::class,
        MbStrContainsRector::class,
        // PHP 8.3
        JsonValidateRector::class,
        // PHP 8.4
        ForeachToArrayAllRector::class,
        ForeachToArrayAnyRector::class,
        ForeachToArrayFindRector::class,
        ForeachToArrayFindKeyRector::class,
    ]);
};
