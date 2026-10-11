<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class ConstTypeNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode
{
    use NodeAttributes;
    public function __construct(public ConstExprNode $constExpr)
    {
    }
    public function __toString(): string
    {
        return $this->constExpr->__toString();
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['constExpr']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
