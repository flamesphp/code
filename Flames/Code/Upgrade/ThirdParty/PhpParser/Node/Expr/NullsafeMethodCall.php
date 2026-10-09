<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArgPlaceholder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
class NullsafeMethodCall extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike
{
    /** @var Identifier|Expr Method name */
    public Node $name;
    /**
     * Constructs a nullsafe method call node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Method name
     * @param array<Arg|VariadicPlaceholder|ArgPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Expr $var, $name, public array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function getSubNodeNames(): array
    {
        return ['var', 'name', 'args'];
    }
    public function getType(): string
    {
        return 'Expr_NullsafeMethodCall';
    }
    public function getRawArgs(): array
    {
        return $this->args;
    }
}
