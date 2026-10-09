<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\Level\CodingStyleLevel;
use Flames\Code\Upgrade\Config\UpgradeConfig;
return static function (UpgradeConfig $rectorConfig): void {
    foreach (CodingStyleLevel::RULES_WITH_CONFIGURATION as $rectorClass => $configuration) {
        $rectorConfig->ruleWithConfiguration($rectorClass, $configuration);
    }
    // the rule order matters, as its used in withCodingStyleLevel() method
    // place the safest rules first, follow by more complex ones
    $rectorConfig->rules(CodingStyleLevel::RULES);
};
