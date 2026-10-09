<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\DeclareItem;
require __DIR__ . '/../DeclareItem.php';
if (\false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\DeclareItem instead.
     */
    class DeclareDeclare extends DeclareItem
    {
    }
}
