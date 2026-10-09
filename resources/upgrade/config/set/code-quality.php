<?php

declare (strict_types=1);
namespace FlamesPrefix202610;

use Flames\Code\Upgrade\Config\Level\CodeQualityLevel;
use Flames\Code\Upgrade\Config\UpgradeConfig;
return static function (UpgradeConfig $rectorConfig): void {
    foreach (CodeQualityLevel::RULES_WITH_CONFIGURATION as $rectorClass => $configuration) {
        $rectorConfig->ruleWithConfiguration($rectorClass, $configuration);
    }
    // the rule order matters, as its used in withCodeQualityLevel() method
    // place the safest rules first, follow by more complex ones
    $rectorConfig->rules(CodeQualityLevel::RULES);
};
