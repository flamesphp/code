<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Printer;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTextNode;
final class EmptyPhpDocDetector
{
    public function isPhpDocNodeEmpty(PhpDocNode $phpDocNode): bool
    {
        if ($phpDocNode->children === []) {
            return \true;
        }
        foreach ($phpDocNode->children as $phpDocChildNode) {
            if ($phpDocChildNode instanceof PhpDocTextNode) {
                if ($phpDocChildNode->text !== '') {
                    return \false;
                }
            } else {
                return \false;
            }
        }
        return \true;
    }
}
