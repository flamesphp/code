<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function implode;
class ConstExprArrayNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode
{
    use NodeAttributes;
    /**
     * @param ConstExprArrayItemNode[] $items
     */
    public function __construct(public array $items)
    {
    }
    public function __toString(): string
    {
        return '[' . implode(', ', $this->items) . ']';
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
