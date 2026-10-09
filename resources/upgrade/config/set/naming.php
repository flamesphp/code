<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Flames\Code\Upgrade\Rules\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Flames\Code\Upgrade\Rules\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Flames\Code\Upgrade\Rules\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Flames\Code\Upgrade\Rules\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Flames\Code\Upgrade\Rules\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([RenameParamToMatchTypeRector::class, RenamePropertyToMatchTypeRector::class, RenameVariableToMatchNewTypeRector::class, RenameVariableToMatchMethodCallReturnTypeRector::class, RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class, RenameForeachValueVariableToMatchExprVariableRector::class]);
};
