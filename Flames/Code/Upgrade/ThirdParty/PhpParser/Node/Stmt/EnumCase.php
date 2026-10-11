<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
class EnumCase extends Node\Stmt
{
    /** @var Node\Identifier Enum case name */
    public Node\Identifier $name;
    /**
     * @param string|Node\Identifier $name Enum case name
     * @param Node\Expr|null $expr Enum case expression
     * @param list<AttributeGroup> $attrGroups PHP attribute groups
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, public ?Node\Expr $expr = null, public array $attrGroups = [], array $attributes = [])
    {
        parent::__construct($attributes);
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
    }
    public function getSubNodeNames(): array
    {
        return ['attrGroups', 'name', 'expr'];
    }
    public function getType(): string
    {
        return 'Stmt_EnumCase';
    }
}
