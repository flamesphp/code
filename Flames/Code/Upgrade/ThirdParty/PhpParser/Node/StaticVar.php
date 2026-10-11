<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class StaticVar extends NodeAbstract
{
    /**
     * Constructs a static variable node.
     *
     * @param Expr\Variable $var Name
     * @param null|Node\Expr $default Default value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable $var, public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $default = null, array $attributes = [])
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
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\StaticVar::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\StaticVar::class);
