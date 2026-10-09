<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\NodeAbstract;
class Arg extends NodeAbstract
{
    /**
     * Constructs a function call argument node.
     *
     * @param Expr $value Value to pass
     * @param bool $byRef Whether to pass by ref
     * @param bool $unpack Whether to unpack the argument
     * @param array<string, mixed> $attributes Additional attributes
     * @param Identifier|null $name Parameter name (for named parameters)
     */
    public function __construct(public \PhpParser\Node\Expr $value, public bool $byRef = \false, public bool $unpack = \false, array $attributes = [], public ?\PhpParser\Node\Identifier $name = null)
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['name', 'value', 'byRef', 'unpack'];
    }
    public function getType(): string
    {
        return 'Arg';
    }
}
