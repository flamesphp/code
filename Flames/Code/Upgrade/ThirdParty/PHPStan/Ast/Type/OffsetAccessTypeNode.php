<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class OffsetAccessTypeNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $type, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $offset)
    {
    }
    public function __toString(): string
    {
        if ($this->type instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\CallableTypeNode || $this->type instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\NullableTypeNode) {
            return '(' . $this->type . ')[' . $this->offset . ']';
        }
        return $this->type . '[' . $this->offset . ']';
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['type'], $properties['offset']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
