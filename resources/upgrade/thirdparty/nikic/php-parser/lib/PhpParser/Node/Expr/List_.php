<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
class List_ extends Expr
{
    // For use in "kind" attribute
    public const KIND_LIST = 1;
    // list() syntax
    public const KIND_ARRAY = 2;
    /**
     * Constructs a list() destructuring node.
     *
     * @param (ArrayItem|null)[] $items List of items to assign to
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $items, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['items'];
    }
    public function getType(): string
    {
        return 'Expr_List';
    }
}
