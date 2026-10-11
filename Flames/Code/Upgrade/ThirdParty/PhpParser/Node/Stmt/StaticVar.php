<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

require __DIR__ . '/../StaticVar.php';
if (\false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\StaticVar instead.
     */
    class StaticVar extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\StaticVar
    {
    }
}
