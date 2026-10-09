<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

class UnionType extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType
{
    /**
     * Constructs a union type.
     *
     * @param (Identifier|Name|IntersectionType)[] $types Types
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $types, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['types'];
    }
    public function getType(): string
    {
        return 'UnionType';
    }
}
