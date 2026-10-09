<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class Do_ extends Node\Stmt
{
    /**
     * Constructs a do while node.
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
        return ['stmts', 'cond'];
    }
    public function getType(): string
    {
        return 'Stmt_Do';
    }
}
