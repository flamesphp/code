<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Bootstrap\ExtensionConfigResolver;
use Flames\Code\Upgrade\Config\UpgradeConfig;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->paths([]);
    $rectorConfig->skip([]);
    $rectorConfig->autoloadPaths([]);
    $rectorConfig->bootstrapFiles([]);
    $rectorConfig->parallel();
    // to avoid autoimporting out of the box
    $rectorConfig->importNames(\false, \false);
    $rectorConfig->removeUnusedImports(\false);
    $rectorConfig->importShortClasses();
    $rectorConfig->indent(' ', 4);
    $rectorConfig->fileExtensions(['php']);
    $rectorConfig->cacheDirectory(\sys_get_temp_dir() . '/flames_code_upgrade_cache');
    $rectorConfig->containerCacheDirectory(\sys_get_temp_dir());
    // load internal rector-* extension configs
    $extensionConfigResolver = new ExtensionConfigResolver();
    foreach ($extensionConfigResolver->provide() as $extensionConfigFile) {
        $rectorConfig->import($extensionConfigFile);
    }
    // use original php-parser printer to avoid BC break on fluent call
    $rectorConfig->newLineOnFluentCall(\false);
    // allow real paths in output formatters
    $rectorConfig->reportingRealPath(\false);
    $rectorConfig->treatClassesAsFinal(\false);
};
