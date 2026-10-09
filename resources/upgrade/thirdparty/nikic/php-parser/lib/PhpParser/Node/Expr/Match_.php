<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node;
use PhpParser\Node\MatchArm;
class Match_ extends Node\Expr
{
    /**
     * @param Node\Expr $cond Condition
     * @param MatchArm[] $arms
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node\Expr $cond, public array $arms = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['cond', 'arms'];
    }
    public function getType(): string
    {
        return 'Expr_Match';
    }
}
