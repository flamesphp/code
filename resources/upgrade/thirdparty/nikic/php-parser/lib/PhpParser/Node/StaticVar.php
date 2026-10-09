<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
class StaticVar extends NodeAbstract
{
    /**
     * Constructs a static variable node.
     *
     * @param Expr\Variable $var Name
     * @param null|Node\Expr $default Default value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public \PhpParser\Node\Expr\Variable $var, public ?\PhpParser\Node\Expr $default = null, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['var', 'default'];
    }
    public function getType(): string
    {
        return 'StaticVar';
    }
}
// @deprecated compatibility alias
class_alias(\PhpParser\Node\StaticVar::class, \PhpParser\Node\Stmt\StaticVar::class);
