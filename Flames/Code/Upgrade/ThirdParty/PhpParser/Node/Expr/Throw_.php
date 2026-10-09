<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class Throw_ extends Node\Expr
{
    /**
     * Constructs a throw expression node.
     *
     * @param Node\Expr $expr Expression
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node\Expr $expr, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['expr'];
    }
    public function getType(): string
    {
        return 'Expr_Throw';
    }
}
