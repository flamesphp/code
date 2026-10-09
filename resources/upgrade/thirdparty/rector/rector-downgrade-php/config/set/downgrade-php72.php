<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector;
use Flames\Code\Upgrade\DowngradePhp72\Rector\ConstFetch\DowngradePhp72JsonConstRector;
use Flames\Code\Upgrade\DowngradePhp72\Rector\FuncCall\DowngradeJsonDecodeNullAssociativeArgRector;
use Flames\Code\Upgrade\DowngradePhp72\Rector\FuncCall\DowngradePregUnmatchedAsNullConstantRector;
use Flames\Code\Upgrade\DowngradePhp72\Rector\FuncCall\DowngradeStreamIsattyRector;
use Flames\Code\Upgrade\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector;
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_71);
    $rectorConfig->rules([DowngradeObjectTypeDeclarationRector::class, DowngradeParameterTypeWideningRector::class, DowngradePregUnmatchedAsNullConstantRector::class, DowngradeStreamIsattyRector::class, DowngradeJsonDecodeNullAssociativeArgRector::class, DowngradePhp72JsonConstRector::class]);
};
