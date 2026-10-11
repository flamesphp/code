<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Stringable;
final class StringNode implements PhpDocTagValueNode
{
    use NodeAttributes;
    public function __construct(public string $value)
    {
        $this->value = str_replace('""', '"', $this->value);
        if (str_contains($this->value, "'") && !str_contains($this->value, "\n")) {
            $kind = String_::KIND_DOUBLE_QUOTED;
        } else {
            $kind = String_::KIND_SINGLE_QUOTED;
        }
        $this->setAttribute(AttributeKey::KIND, $kind);
    }
    public function __toString(): string
    {
        return '"' . str_replace('"', '""', $this->value) . '"';
    }
}
