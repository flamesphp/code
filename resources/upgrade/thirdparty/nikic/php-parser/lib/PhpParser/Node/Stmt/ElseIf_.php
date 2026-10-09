<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
class ElseIf_ extends Node\Stmt
{
    /**
     * Constructs an elseif node.
     *
     * @param Node\Expr $cond Condition
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node\Expr $cond, public array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['cond', 'stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_ElseIf';
    }
}
