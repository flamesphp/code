<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php83\Rector\BooleanAnd\JsonValidateRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\Class_\ReadOnlyAnonymousClassRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\ClassConst\AddTypeToConstRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\CombineHostPortLdapUriRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\DynamicClassConstFetchRector;
use Flames\Code\Upgrade\Rules\Php83\Rector\FuncCall\RemoveGetClassGetParentClassNoArgsRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([AddTypeToConstRector::class, CombineHostPortLdapUriRector::class, RemoveGetClassGetParentClassNoArgsRector::class, ReadOnlyAnonymousClassRector::class, DynamicClassConstFetchRector::class, JsonValidateRector::class]);
};
