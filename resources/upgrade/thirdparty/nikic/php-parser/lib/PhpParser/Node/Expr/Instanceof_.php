<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
class Instanceof_ extends Expr
{
    /**
     * Constructs an instanceof check node.
     *
     * @param Expr $expr Expression
     * @param Name|Expr $class Class name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Expr $expr, public Node $class, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['expr', 'class'];
    }
    public function getType(): string
    {
        return 'Expr_Instanceof';
    }
}
