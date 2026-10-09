<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class ArrayItem extends NodeAbstract
{
    /**
     * Constructs an array item node.
     *
     * @param Expr $value Value
     * @param null|Expr $key Key
     * @param bool $byRef Whether to assign by reference
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $value, public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $key = null, public bool $byRef = \false, array $attributes = [], /** @var bool Whether to unpack the argument */
    public bool $unpack = \false)
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['key', 'value', 'byRef', 'unpack'];
    }
    public function getType(): string
    {
        return 'ArrayItem';
    }
}
// @deprecated compatibility alias
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayItem::class);
