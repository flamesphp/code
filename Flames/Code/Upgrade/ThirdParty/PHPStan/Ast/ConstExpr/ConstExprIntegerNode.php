<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class ConstExprIntegerNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode
{
    use NodeAttributes;
    public function __construct(public string $value)
    {
    }
    public function __toString(): string
    {
        return $this->value;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['value']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
