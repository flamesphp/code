<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\Level\TypeDeclarationDocblocksLevel;
use Flames\Code\Upgrade\Config\UpgradeConfig;
/**
 * @experimental * 2025-09, experimental hidden set for type declaration in docblocks
 */
return static function (UpgradeConfig $rectorConfig): void {
    $rectorConfig->rules(TypeDeclarationDocblocksLevel::RULES);
};
