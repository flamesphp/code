<?php

declare (strict_types=1);
namespace PHPStan\PhpDocParser\Ast\PhpDoc\Doctrine;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use function trim;
class DoctrineTagValueNode implements PhpDocTagValueNode
{
    use NodeAttributes;
    public function __construct(
        public \PHPStan\PhpDocParser\Ast\PhpDoc\Doctrine\DoctrineAnnotation $annotation,
        /** @var string (may be empty) */
        public string $description
    )
    {
    }
    public function __toString(): string
    {
        return trim("{$this->annotation} {$this->description}");
    }
    /**
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): self
    {
        $instance = new self($properties['annotation'], $properties['description']);
        if (isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                $instance->setAttribute($key, $value);
            }
        }
        return $instance;
    }
}
