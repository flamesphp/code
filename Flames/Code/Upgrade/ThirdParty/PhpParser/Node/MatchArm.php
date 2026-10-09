<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class MatchArm extends NodeAbstract
{
    /**
     * @param null|list<Node\Expr> $conds
     */
    public function __construct(public ?array $conds, public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $body, array $attributes = [])
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
