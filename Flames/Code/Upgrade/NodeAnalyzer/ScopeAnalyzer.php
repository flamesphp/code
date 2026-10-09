<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
final class ScopeAnalyzer
{
    /**
     * @var array<class-string<Node>>
     */
    private const array NON_REFRESHABLE_NODES = [Name::class, Identifier::class, ComplexType::class];
    public function isRefreshable(Node $node): bool
    {
        $found = array_all(self::NON_REFRESHABLE_NODES, fn($noScopeNode) => !$node instanceof $noScopeNode);
        return $found;
    }
}
