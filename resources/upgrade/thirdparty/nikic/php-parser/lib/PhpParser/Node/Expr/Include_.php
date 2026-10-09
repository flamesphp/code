<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node\Expr;
class Include_ extends Expr
{
    public const TYPE_INCLUDE = 1;
    public const TYPE_INCLUDE_ONCE = 2;
    public const TYPE_REQUIRE = 3;
    public const TYPE_REQUIRE_ONCE = 4;
    /**
     * Constructs an include node.
     *
     * @param Expr $expr Expression
     * @param int $type Type of include
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Expr $expr, public int $type, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['expr', 'type'];
    }
    public function getType(): string
    {
        return 'Expr_Include';
    }
}
