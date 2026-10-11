<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
class ConstFetchNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprNode
{
    use NodeAttributes;
    public function __construct(
        /** @var string class name for class constants or empty string for non-class constants */
        public string $className,
        public string $name
    )
    {
    }
    public function __toString(): string
    {
        if ($this->className === '') {
            return $this->name;
        }
        return "{$this->className}::{$this->name}";
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['className'], $properties['name']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
