<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
class GroupUse extends Stmt
{
    /**
     * Constructs a group use node.
     *
     * @param Name $prefix Prefix for uses
     * @param UseItem[] $uses Uses
     * @param Use_::TYPE_* $type Type of group use
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Name $prefix, public array $uses, public int $type = \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_::TYPE_NORMAL, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['type', 'prefix', 'uses'];
    }
    public function getType(): string
    {
        return 'Stmt_GroupUse';
    }
}
