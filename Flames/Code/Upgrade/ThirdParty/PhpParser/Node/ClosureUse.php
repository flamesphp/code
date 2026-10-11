<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class ClosureUse extends NodeAbstract
{
    /**
     * Constructs a closure use node.
     *
     * @param Expr\Variable $var Variable to use
     * @param bool $byRef Whether to use by reference
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable $var, public bool $byRef = \false, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['var', 'byRef'];
    }
    public function getType(): string
    {
        return 'ClosureUse';
    }
}
// @deprecated compatibility alias
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClosureUse::class);
