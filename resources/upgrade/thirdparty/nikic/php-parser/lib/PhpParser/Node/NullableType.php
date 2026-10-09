<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\Node;
class NullableType extends \PhpParser\Node\ComplexType
{
    /**
     * Constructs a nullable type (wrapping another type).
     *
     * @param Identifier|Name $type Type
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node $type, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['type'];
    }
    public function getType(): string
    {
        return 'NullableType';
    }
}
