<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\ValueObject;

use PhpParser\Node\Expr;
final readonly class ExplicitBoolCondition
{
    public function __construct(private Expr $expr, private bool $isNegated)
    {
    }
    public function getConditionNode(): Expr
    {
        return $this->expr;
    }
    public function isNegated(): bool
    {
        return $this->isNegated;
    }
}
