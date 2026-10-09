<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
class Switch_ extends Node\Stmt
{
    /**
     * Constructs a case node.
     *
     * @param Node\Expr $cond Condition
     * @param Case_[] $cases Case list
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node\Expr $cond, public array $cases, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['cond', 'cases'];
    }
    public function getType(): string
    {
        return 'Stmt_Switch';
    }
}
