<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Contract\PhpDocParser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocNode;
interface PhpDocNodeDecoratorInterface
{
    public function decorate(PhpDocNode $phpDocNode, Node $phpNode): void;
}
