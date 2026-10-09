<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindKeyRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall\AddEscapeArgumentRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\FuncCall\RoundingModeEnumRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Flames\Code\Upgrade\Rules\Php84\Rector\Param\ExplicitNullableParamTypeRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([ExplicitNullableParamTypeRector::class, RoundingModeEnumRector::class, AddEscapeArgumentRector::class, NewMethodCallWithoutParenthesesRector::class, ForeachToArrayFindRector::class, ForeachToArrayFindKeyRector::class, ForeachToArrayAllRector::class, ForeachToArrayAnyRector::class]);
};
