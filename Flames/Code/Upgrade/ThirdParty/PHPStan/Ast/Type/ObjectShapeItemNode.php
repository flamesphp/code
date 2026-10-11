<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstExprStringNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use function sprintf;
class ObjectShapeItemNode implements Node
{
    use NodeAttributes;
    /**
     * @param ConstExprStringNode|IdentifierTypeNode $keyName
     */
    public function __construct(public $keyName, public bool $optional, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode $valueType)
    {
    }
    public function __toString(): string
    {
        if ($this->keyName !== null) {
            return sprintf('%s%s: %s', (string) $this->keyName, $this->optional ? '?' : '', (string) $this->valueType);
        }
        return (string) $this->valueType;
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['keyName'], $properties['optional'], $properties['valueType']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
