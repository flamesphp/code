<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\Comparing;

use PhpParser\Node;
use Flames\Code\Upgrade\Comments\CommentRemover;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
final readonly class NodeComparator
{
    public function __construct(private CommentRemover $commentRemover, private BetterStandardPrinter $betterStandardPrinter)
    {
    }
    /**
     * Removes all comments from both nodes
     * @param Node|Node[]|null $node
     */
    public function printWithoutComments($node): string
    {
        $node = $this->commentRemover->removeFromNode($node);
        $content = $this->betterStandardPrinter->print($node);
        return trim($content);
    }
    /**
     * @param Node|Node[]|null $firstNode
     * @param Node|Node[]|null $secondNode
     */
    public function areNodesEqual($firstNode, $secondNode): bool
    {
        if ($firstNode instanceof Node && !$secondNode instanceof Node) {
            return \false;
        }
        if (!$firstNode instanceof Node && $secondNode instanceof Node) {
            return \false;
        }
        if (is_array($firstNode) && !is_array($secondNode)) {
            return \false;
        }
        if (!is_array($secondNode)) {
            return $this->printWithoutComments($firstNode) === $this->printWithoutComments($secondNode);
        }
        if (is_array($firstNode)) {
            return $this->printWithoutComments($firstNode) === $this->printWithoutComments($secondNode);
        }
        return \false;
    }
    /**
     * @api
     * @param Node[] $availableNodes
     */
    public function isNodeEqual(Node $singleNode, array $availableNodes): bool
    {
        $found = array_any($availableNodes, fn($availableNode) => $this->areNodesEqual($singleNode, $availableNode));
        return $found;
    }
    /**
     * Checks even clone nodes
     */
    public function areSameNode(Node $firstNode, Node $secondNode): bool
    {
        if ($firstNode === $secondNode) {
            return \true;
        }
        $firstClass = $firstNode::class;
        $secondClass = $secondNode::class;
        if ($firstClass !== $secondClass) {
            return \false;
        }
        if ($firstNode->getStartTokenPos() !== $secondNode->getStartTokenPos()) {
            return \false;
        }
        if ($firstNode->getEndTokenPos() !== $secondNode->getEndTokenPos()) {
            return \false;
        }
        $printFirstNode = $this->betterStandardPrinter->print($firstNode);
        $printSecondNode = $this->betterStandardPrinter->print($secondNode);
        return $printFirstNode === $printSecondNode;
    }
}
