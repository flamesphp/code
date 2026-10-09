<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
class NullsafePropertyFetch extends Expr
{
    /** @var Identifier|Expr Property name */
    public Node $name;
    /**
     * Constructs a nullsafe property fetch node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Property name
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Expr $var, $name, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function getSubNodeNames(): array
    {
        return ['var', 'name'];
    }
    public function getType(): string
    {
        return 'Expr_NullsafePropertyFetch';
    }
}
