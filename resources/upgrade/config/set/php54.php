<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Flames\Code\Upgrade\Rules\Php54\Rector\Break_\RemoveZeroBreakContinueRector;
use Flames\Code\Upgrade\Rules\Php54\Rector\FuncCall\RemoveReferenceFromCallRector;
use Flames\Code\Upgrade\Rules\Renaming\Rector\FuncCall\RenameFunctionRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([LongArrayToShortArrayRector::class, RemoveReferenceFromCallRector::class, RemoveZeroBreakContinueRector::class]);
    $rectorConfig->ruleWithConfiguration(RenameFunctionRector::class, ['mysqli_param_count' => 'mysqli_stmt_param_count']);
};
