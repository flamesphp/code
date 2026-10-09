<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\InterpolatedStringPart;
require __DIR__ . '/../InterpolatedStringPart.php';
if (\false) {
    /**
     * For classmap-authoritative support.
     *
     * @deprecated use \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\InterpolatedStringPart instead.
     */
    class EncapsedStringPart extends InterpolatedStringPart
    {
    }
}
