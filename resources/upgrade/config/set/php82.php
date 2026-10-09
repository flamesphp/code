<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Rules\Php82\Rector\Class_\ReadOnlyClassRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector;
use Flames\Code\Upgrade\Rules\Php82\Rector\New_\FilesystemIteratorSkipDotsRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([ReadOnlyClassRector::class, Utf8DecodeEncodeToMbConvertEncodingRector::class, FilesystemIteratorSkipDotsRector::class, VariableInStringInterpolationFixerRector::class]);
};
