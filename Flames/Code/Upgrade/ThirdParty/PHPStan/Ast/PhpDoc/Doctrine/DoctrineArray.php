<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\Doctrine;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function implode;
class DoctrineArray implements Node
{
    use NodeAttributes;
    /**
     * @param list<DoctrineArrayItem> $items
     */
    public function __construct(public array $items)
    {
    }
    public function __toString(): string
    {
        $items = implode(', ', $this->items);
        return '{' . $items . '}';
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
