<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php52\Rector\Property\VarToPublicPropertyRector;
use Flames\Code\Upgrade\Rules\Php52\Rector\Switch_\ContinueToBreakInSwitchRector;
use Flames\Code\Upgrade\Rules\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Flames\Code\Upgrade\Rules\Removing\ValueObject\RemoveFuncCallArg;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([VarToPublicPropertyRector::class, ContinueToBreakInSwitchRector::class]);
    $rectorConfig->ruleWithConfiguration(RemoveFuncCallArgRector::class, [
        // see https://www.php.net/manual/en/function.ldap-first-attribute.php
        new RemoveFuncCallArg('ldap_first_attribute', 2),
    ]);
};
