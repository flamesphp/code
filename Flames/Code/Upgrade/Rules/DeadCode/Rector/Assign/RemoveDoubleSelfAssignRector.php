<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Assign;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Assign\RemoveDoubleSelfAssignRectorTest
 */
final class RemoveDoubleSelfAssignRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove duplicated self assign of the same variable', [new CodeSample('$validator = $validator = Validation::createValidator();', '$validator = Validation::createValidator();')]);
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
        if (!$node->var instanceof Variable) {
            return null;
        }
        if (!$node->expr instanceof Assign) {
            return null;
        }
        $innerAssign = $node->expr;
        if (!$innerAssign->var instanceof Variable) {
            return null;
        }
        if (!$this->nodeComparator->areNodesEqual($node->var, $innerAssign->var)) {
            return null;
        }
        return $innerAssign;
    }
}
