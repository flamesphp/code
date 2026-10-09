<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Attribute\ExplicitAttributeNamedArgsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\CallLike\AddNameToBooleanArgumentRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\CallLike\AddNameToNullArgumentRector;
use Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\DeadCode\Rector\MethodCall\RemoveNullNamedArgOnNullDefaultParamRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([AddNameToNullArgumentRector::class, AddNameToBooleanArgumentRector::class, RemoveNullNamedArgOnNullDefaultParamRector::class, SortCallLikeNamedArgsRector::class, SortAttributeNamedArgsRector::class, ExplicitAttributeNamedArgsRector::class]);
};
