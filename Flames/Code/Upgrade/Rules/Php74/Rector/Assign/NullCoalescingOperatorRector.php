<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\Assign;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\Assign\NullCoalescingOperatorRectorTest
 */
final class NullCoalescingOperatorRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use null coalescing operator `??=`', [new CodeSample(<<<'CODE_SAMPLE'
$array = [];
$array['user_id'] = $array['user_id'] ?? 'value';
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$array = [];
$array['user_id'] ??= 'value';
CODE_SAMPLE
)]);
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
    public function refactor(Node $node): ?AssignCoalesce
    {
        if (!$node->expr instanceof Coalesce) {
            return null;
        }
        if (!$this->nodeComparator->areNodesEqual($node->var, $node->expr->left)) {
            return null;
        }
        return new AssignCoalesce($node->var, $node->expr->right);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NULL_COALESCE_ASSIGN;
    }
}
