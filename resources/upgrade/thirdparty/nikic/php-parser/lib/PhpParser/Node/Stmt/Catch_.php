<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
use PhpParser\Node\Expr;
class Catch_ extends Node\Stmt
{
    /**
     * Constructs a catch node.
     *
     * @param Node\Name[] $types Types of exceptions to catch
     * @param Expr\Variable|null $var Variable for exception
     * @param Node\Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $types, public ?Expr\Variable $var = null, public array $stmts = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['types', 'var', 'stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_Catch';
    }
}
