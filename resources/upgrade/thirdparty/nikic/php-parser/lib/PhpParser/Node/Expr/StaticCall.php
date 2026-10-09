<?php

declare (strict_types=1);
namespace PhpParser\Node\Expr;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\VariadicPlaceholder;
class StaticCall extends \PhpParser\Node\Expr\CallLike
{
    /** @var Identifier|Expr Method name */
    public Node $name;
    /**
     * Constructs a static method call node.
     *
     * @param Node\Name|Expr $class Class name
     * @param string|Identifier|Expr $name Method name
     * @param array<Arg|VariadicPlaceholder|ArgPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node $class, $name, public array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }
    public function getSubNodeNames(): array
    {
        return ['class', 'name', 'args'];
    }
    public function getType(): string
    {
        return 'Expr_StaticCall';
    }
    public function getRawArgs(): array
    {
        return $this->args;
    }
}
