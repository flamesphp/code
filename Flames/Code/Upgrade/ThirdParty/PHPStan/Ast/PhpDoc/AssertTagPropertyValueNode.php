<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use function trim;
class AssertTagPropertyValueNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode
{
    use NodeAttributes;
    public function __construct(
        public TypeNode $type,
        public string $parameter,
        public string $property,
        public bool $isNegated,
        /** @var string (may be empty) */
        public string $description,
        public bool $isEquality
    )
    {
    }
    public function __toString(): string
    {
        $isNegated = $this->isNegated ? '!' : '';
        $isEquality = $this->isEquality ? '=' : '';
        return trim("{$isNegated}{$isEquality}{$this->type} {$this->parameter}->{$this->property} {$this->description}");
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['type'], $properties['parameter'], $properties['property'], $properties['isNegated'], $properties['description'], $properties['isEquality']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
