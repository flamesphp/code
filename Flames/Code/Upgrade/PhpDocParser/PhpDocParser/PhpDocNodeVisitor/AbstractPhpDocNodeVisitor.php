<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\Contract\PhpDocNodeVisitorInterface;
/**
 * Inspired by https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitorAbstract.php
 */
abstract class AbstractPhpDocNodeVisitor implements PhpDocNodeVisitorInterface
{
    public function beforeTraverse(Node $node): void
    {
    }
    /**
     * @return int|\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node|null
     */
    public function enterNode(Node $node)
    {
        return null;
    }
    /**
     * @return null|int|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node|Node[] Replacement node (or special return)
     */
    public function leaveNode(Node $node)
    {
        return null;
    }
    public function afterTraverse(Node $node): void
    {
    }
}
