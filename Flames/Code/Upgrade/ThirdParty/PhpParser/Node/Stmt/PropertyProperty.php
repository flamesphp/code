<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
require __DIR__ . '/../PropertyItem.php';
if (\false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem instead.
     */
    class PropertyProperty extends PropertyItem
    {
    }
}
