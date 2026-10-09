<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\NodeAttributes;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use function trim;
class VarTagValueNode implements \Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagValueNode
{
    use NodeAttributes;
    public function __construct(
        public TypeNode $type,
        /** @var string (may be empty) */
        public string $variableName,
        /** @var string (may be empty) */
        public string $description
    )
    {
    }
    public function __toString(): string
    {
        return trim("{$this->type} " . trim("{$this->variableName} {$this->description}"));
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['type'], $properties['variableName'], $properties['description']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
