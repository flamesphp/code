<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class Foreach_ extends Node\Stmt
{
    /** @var null|Node\Expr Variable to assign key to */
    public ?Node\Expr $keyVar;
    /** @var bool Whether to assign value by reference */
    public bool $byRef;
    /** @var Node\Stmt[] Statements */
    public array $stmts;
    /**
     * Constructs a foreach node.
     *
     * @param Node\Expr $expr Expression to iterate
     * @param Node\Expr $valueVar Variable to assign value to
     * @param array{
     *     keyVar?: Node\Expr|null,
     *     byRef?: bool,
     *     stmts?: Node\Stmt[],
     * } $subNodes Array of the following optional subnodes:
     *             'keyVar' => null   : Variable to assign key to
     *             'byRef'  => false  : Whether to assign value by reference
     *             'stmts'  => array(): Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node\Expr $expr, public Node\Expr $valueVar, array $subNodes = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->keyVar = $subNodes['keyVar'] ?? null;
        $this->byRef = $subNodes['byRef'] ?? \false;
        $this->stmts = $subNodes['stmts'] ?? [];
    }
    public function getSubNodeNames(): array
    {
        return ['expr', 'keyVar', 'byRef', 'valueVar', 'stmts'];
    }
    public function getType(): string
    {
        return 'Stmt_Foreach';
    }
}
