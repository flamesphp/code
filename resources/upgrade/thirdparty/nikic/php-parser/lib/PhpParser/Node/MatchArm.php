<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
class MatchArm extends NodeAbstract
{
    /**
     * @param null|list<Node\Expr> $conds
     */
    public function __construct(public ?array $conds, public \PhpParser\Node\Expr $body, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['conds', 'body'];
    }
    public function getType(): string
    {
        return 'MatchArm';
    }
}
