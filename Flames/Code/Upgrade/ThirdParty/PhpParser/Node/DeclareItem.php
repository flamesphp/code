<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class DeclareItem extends NodeAbstract
{
    /** @var Node\Identifier Key */
    public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier $key;
    /**
     * Constructs a declare key=>value pair node.
     *
     * @param string|Node\Identifier $key Key
     * @param Node\Expr $value Value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($key, public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->key = \is_string($key) ? new Node\Identifier($key) : $key;
    }
    public function getSubNodeNames(): array
    {
        return ['key', 'value'];
    }
    public function getType(): string
    {
        return 'DeclareItem';
    }
}
// @deprecated compatibility alias
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\DeclareItem::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\DeclareDeclare::class);
