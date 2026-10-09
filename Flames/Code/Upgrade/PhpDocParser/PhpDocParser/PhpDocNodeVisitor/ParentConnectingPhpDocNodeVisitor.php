<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor;

use PHPStan\PhpDocParser\Ast\Node;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\ValueObject\PhpDocAttributeKey;
/**
 * @api
 *
 * Mimics https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitor/ParentConnectingVisitor.php
 *
 * @see \Flames\Code\Upgrade\Tests\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\ParentConnectingPhpDocNodeVisitorTest
 */
final class ParentConnectingPhpDocNodeVisitor extends \Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor
{
    /**
     * @var Node[]
     */
    private array $stack = [];
    public function beforeTraverse(Node $node): void
    {
        $this->stack = [$node];
    }
    public function enterNode(Node $node): Node
    {
        if ($this->stack !== []) {
            $parentNode = $this->stack[count($this->stack) - 1];
            $node->setAttribute(PhpDocAttributeKey::PARENT, $parentNode);
        }
        $this->stack[] = $node;
        return $node;
    }
    /**
     * @return null|int|\PhpParser\Node|Node[] Replacement node (or special return
     */
    public function leaveNode(Node $node)
    {
        array_pop($this->stack);
        return null;
    }
}
