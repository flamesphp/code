<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\Doctrine;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprIntegerNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprStringNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstFetchNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
/**
 * @phpstan-import-type ValueType from DoctrineArgument
 * @phpstan-type KeyType = ConstExprIntegerNode|ConstExprStringNode|IdentifierTypeNode|ConstFetchNode|null
 */
class DoctrineArrayItem implements Node
{
    use NodeAttributes;
    /**
     * @param KeyType $key
     * @param ValueType $value
     */
    public function __construct(public $key, public $value)
    {
    }
    public function __toString(): string
    {
        if ($this->key === null) {
            return (string) $this->value;
        }
        return $this->key . '=' . $this->value;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['key'], $properties['value']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
