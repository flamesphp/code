<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type;

use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use Stringable;
final class BracketsAwareIntersectionTypeNode extends IntersectionTypeNode
{
    public function __toString(): string
    {
        return implode('&', $this->types);
    }
}
