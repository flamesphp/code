<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpDocParser\PhpDocParser\Contract;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
/**
 * Inspired by https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitor.php
 */
interface PhpDocNodeVisitorInterface
{
    public function beforeTraverse(Node $node): void;
    /**
     * @return int|\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node|null
     */
    public function enterNode(Node $node);
    /**
     * @return null|int|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node|Node[] Replacement node (or special return)
     */
    public function leaveNode(Node $node);
    public function afterTraverse(Node $node): void;
}
