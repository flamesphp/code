<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Stringable;
/**
 * Useful for annotation class based annotation, e.g. @ORM\Entity to prevent space
 * between the @ORM\Entity and (someContent)
 */
final class SpacelessPhpDocTagNode extends PhpDocTagNode
{
    public function __toString(): string
    {
        return $this->name . $this->value;
    }
}
