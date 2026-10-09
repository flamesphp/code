<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\VariadicPlaceholder;
class MethodCall extends \PhpParser\Node\Expr\CallLike
{
    /** @var Identifier|Expr Method name */
    public Node $name;
    /**
     * Constructs a function call node.
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
        return 'Expr_MethodCall';
    }
    public function getRawArgs(): array
    {
        return $this->args;
    }
}
