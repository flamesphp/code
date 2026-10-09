<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser;

/**
 * @codeCoverageIgnore
 */
abstract class NodeVisitorAbstract implements \Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor
{
    public function beforeTraverse(array $nodes)
    {
        return null;
    }
    public function enterNode(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node $node)
    {
        return null;
    }
    public function leaveNode(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node $node)
    {
        return null;
    }
    public function afterTraverse(array $nodes)
    {
        return null;
    }
}
