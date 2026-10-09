<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IntersectionTypeNode;
use Stringable;
final class BracketsAwareIntersectionTypeNode extends IntersectionTypeNode
{
    public function __toString(): string
    {
        return implode('&', $this->types);
    }
}
