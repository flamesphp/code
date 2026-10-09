<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Assign;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use Flames\Code\Upgrade\PhpParser\Node\AssignAndBinaryMap;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Assign\CombinedAssignRectorTest
 */
final class CombinedAssignRector extends AbstractRector
{
    public function __construct(private readonly AssignAndBinaryMap $assignAndBinaryMap)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Simplify $value = $value + 5; assignments to shorter ones', [new CodeSample('$value = $value + 5;', '$value += 5;')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Assign::class];
    }
    /**
     * @param Assign $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->expr instanceof BinaryOp) {
            return null;
        }
        $binaryNode = $node->expr;
        if (!$this->nodeComparator->areNodesEqual($node->var, $binaryNode->left)) {
            return null;
        }
        if ($binaryNode->left instanceof ArrayDimFetch && $node->expr instanceof BitwiseXor) {
            return null;
        }
        $assignClass = $this->assignAndBinaryMap->getAlternative($binaryNode);
        if ($assignClass === null) {
            return null;
        }
        return new $assignClass($node->var, $binaryNode->right);
    }
}
