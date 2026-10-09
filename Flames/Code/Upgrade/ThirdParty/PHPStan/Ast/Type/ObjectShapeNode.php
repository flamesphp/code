<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function implode;
class ObjectShapeNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    /**
     * @param ObjectShapeItemNode[] $items
     */
    public function __construct(public array $items)
    {
    }
    public function __toString(): string
    {
        $items = $this->items;
        return 'object{' . implode(', ', $items) . '}';
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['items']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
