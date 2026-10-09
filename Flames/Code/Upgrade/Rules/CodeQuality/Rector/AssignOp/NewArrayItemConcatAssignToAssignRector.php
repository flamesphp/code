<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\AssignOp;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Concat;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\AssignOp\NewArrayItemConcatAssignToAssignRectorTest
 */
final class NewArrayItemConcatAssignToAssignRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change concat assign on a new array item to plain assign, as the new item is always null', [new CodeSample(<<<'CODE_SAMPLE'
$values[] .= $name;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$values[] = $name;
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Concat::class];
    }
    /**
     * @param Concat $node
     */
    public function refactor(Node $node): ?Assign
    {
        if (!$node->var instanceof ArrayDimFetch) {
            return null;
        }
        if ($node->var->dim instanceof Node) {
            return null;
        }
        return new Assign($node->var, $node->expr);
    }
}
