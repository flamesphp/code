<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\TypedCollections\Rector\Class_\CompletePropertyDocblockFromToManyRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\CollectionDocblockGenericTypeRector;
use Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\DefaultCollectionKeyRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules([
        // safe rules that handle only docblocks
        CollectionDocblockGenericTypeRector::class,
        DefaultCollectionKeyRector::class,
        CompletePropertyDocblockFromToManyRector::class,
    ]);
};
