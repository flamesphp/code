<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
class Attribute extends NodeAbstract
{
    /**
     * @param Node\Name $name Attribute name
     * @param list<Arg> $args Attribute arguments
     * @param array<string, mixed> $attributes Additional node attributes
     */
    public function __construct(public \PhpParser\Node\Name $name, public array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['name', 'args'];
    }
    public function getType(): string
    {
        return 'Attribute';
    }
}
