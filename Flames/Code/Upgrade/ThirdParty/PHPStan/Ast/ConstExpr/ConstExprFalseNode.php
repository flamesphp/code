<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class ConstExprFalseNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode
{
    use NodeAttributes;
    public function __toString(): string
    {
        return 'false';
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self();
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
