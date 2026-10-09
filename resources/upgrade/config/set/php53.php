<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRector;
use Flames\Code\Upgrade\Rules\Php53\Rector\Ternary\TernaryToElvisRector;
use Flames\Code\Upgrade\Rules\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector;
use Flames\Code\Upgrade\Rules\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([ExplicitPublicClassMethodRector::class, TernaryToElvisRector::class, DirNameFileConstantToDirConstantRector::class, ReplaceHttpServerVarsByServerRector::class]);
};
