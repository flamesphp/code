<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

class IntersectionType extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType
{
    /**
     * Constructs an intersection type.
     *
     * @param (Identifier|Name)[] $types Types
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
        return 'IntersectionType';
    }
}
