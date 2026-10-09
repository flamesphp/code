<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
class Case_ extends Node\Stmt
{
    /**
     * Constructs a case node.
     *
     * @param null|Node\Expr $cond Condition (null for default)
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public ?Node\Expr $cond, public array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['cond', 'stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_Case';
    }
}
