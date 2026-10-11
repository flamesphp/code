<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function array_map;
use function implode;
class UnionTypeNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    /**
     * @param TypeNode[] $types
     */
    public function __construct(public array $types)
    {
    }
    public function __toString(): string
    {
        return '(' . implode(' | ', array_map(static function (\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $type): string {
            if ($type instanceof \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\NullableTypeNode) {
                return '(' . $type . ')';
            }
            return (string) $type;
        }, $this->types)) . ')';
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['types']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
