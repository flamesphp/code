<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node;
use PhpParser\Node\DeclareItem;
class Declare_ extends Node\Stmt
{
    /**
     * Constructs a declare node.
     *
     * @param DeclareItem[] $declares List of declares
     * @param Node\Stmt[]|null $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $declares, public ?array $stmts = null, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['declares', 'stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_Declare';
    }
}
