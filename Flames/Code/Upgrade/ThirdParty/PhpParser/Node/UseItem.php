<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
class UseItem extends NodeAbstract
{
    /** @var Identifier|null Alias */
    public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier $alias;
    /**
     * Constructs an alias (use) item node.
     *
     * @param Node\Name $name Namespace/Class to alias
     * @param null|string|Identifier $alias Alias
     * @param Use_::TYPE_* $type Type of the use element (for mixed group use only)
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name $name, $alias = null, public int $type = Use_::TYPE_UNKNOWN, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->alias = \is_string($alias) ? new \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier($alias) : $alias;
    }
    public function getSubNodeNames(): array
    {
        return ['type', 'name', 'alias'];
    }
    /**
     * Get alias. If not explicitly given this is the last component of the used name.
     */
    public function getAlias(): \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier
    {
        if (null !== $this->alias) {
            return $this->alias;
        }
        return new \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier($this->name->getLast());
    }
    public function getType(): string
    {
        return 'UseItem';
    }
}
// @deprecated compatibility alias
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\UseUse::class);
