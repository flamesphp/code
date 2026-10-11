<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class NullableTypeNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $type)
    {
    }
    public function __toString(): string
    {
        if ($this->type instanceof self) {
            // "??Foo" is no type at all, so the one written inside keeps the
            // parentheses it was read with
            return '?(' . $this->type . ')';
        }
        return '?' . $this->type;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['type']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
