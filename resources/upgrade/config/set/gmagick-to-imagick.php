<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Config\Set;

use Flames\Code\Upgrade\Config\UpgradeConfig;
# @deprecated Niche set for a rarely used extension, it is empty now and will be removed.
# Register RenameClassUpgrade and RenameMethodUpgrade with your own configuration instead.
return static function (UpgradeConfig $rectorConfig): void {
};
