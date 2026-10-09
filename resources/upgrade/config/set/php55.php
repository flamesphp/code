<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\ClassConstFetch\StaticToSelfOnFinalClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\GetCalledClassToSelfClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\GetCalledClassToStaticClassRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\FuncCall\PregReplaceEModifierRector;
use Flames\Code\Upgrade\Rules\Php55\Rector\String_\StringClassNameToClassConstantRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([StringClassNameToClassConstantRector::class, ClassConstantToSelfClassRector::class, PregReplaceEModifierRector::class, GetCalledClassToSelfClassRector::class, GetCalledClassToStaticClassRector::class, StaticToSelfOnFinalClassRector::class]);
};
