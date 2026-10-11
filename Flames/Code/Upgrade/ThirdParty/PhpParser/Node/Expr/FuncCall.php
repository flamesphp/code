<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
class FuncCall extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike
{
    /**
     * Constructs a function call node.
     *
     * @param Node\Name|Expr $name Function name
     * @param array<Node\Arg|Node\VariadicPlaceholder|Node\ArgPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node $name, public array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['name', 'args'];
    }
    public function getType(): string
    {
        return 'Expr_FuncCall';
    }
    public function getRawArgs(): array
    {
        return $this->args;
    }
}
