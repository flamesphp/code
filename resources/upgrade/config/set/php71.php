<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php71\Rector\Assign\AssignArrayToStringRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\BinaryOp\BinaryOpBetweenNumberAndStringRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\BooleanOr\IsIterableRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\List_\ListToArrayDestructRector;
use Flames\Code\Upgrade\Rules\Php71\Rector\TryCatch\MultiExceptionCatchRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([IsIterableRector::class, MultiExceptionCatchRector::class, AssignArrayToStringRector::class, RemoveExtraParametersRector::class, BinaryOpBetweenNumberAndStringRector::class, ListToArrayDestructRector::class]);
};
