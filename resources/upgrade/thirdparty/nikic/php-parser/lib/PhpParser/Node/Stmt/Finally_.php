<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
class Finally_ extends Node\Stmt
{
    /**
     * Constructs a finally node.
     *
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_Finally';
    }
}
