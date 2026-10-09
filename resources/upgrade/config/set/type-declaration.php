<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\Level\TypeDeclarationLevel;
use Flames\Code\Upgrade\Config\UpgradeConfig;
return static function (UpgradeConfig $rectorConfig): void {
    // the rule order matters, as its used in withTypeCoverageLevel() method
    // place the safest rules first, follow by more complex ones
    $rectorConfig->rules(TypeDeclarationLevel::RULES);
};
