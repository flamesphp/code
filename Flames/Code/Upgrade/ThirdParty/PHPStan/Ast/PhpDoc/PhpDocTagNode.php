<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\Doctrine\DoctrineTagValueNode;
use function trim;
class PhpDocTagNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode
{
    use NodeAttributes;
    public function __construct(public string $name, public \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode $value)
    {
    }
    public function __toString(): string
    {
        if ($this->value instanceof DoctrineTagValueNode) {
            return (string) $this->value;
        }
        return trim("{$this->name} {$this->value}");
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['name'], $properties['value']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
