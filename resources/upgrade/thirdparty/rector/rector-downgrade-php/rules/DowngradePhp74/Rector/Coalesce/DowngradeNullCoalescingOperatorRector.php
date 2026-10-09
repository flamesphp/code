<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp74\Rector\Coalesce;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/null_coalesce_equal_operator
 * @see \Flames\Code\Upgrade\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRectorTest
 */
final class DowngradeNullCoalescingOperatorRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove null coalescing operator ??=', [new CodeSample(<<<'CODE_SAMPLE'
$array = [];
$array['user_id'] ??= 'value';
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$array = [];
$array['user_id'] = $array['user_id'] ?? 'value';
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [AssignCoalesce::class];
    }
    /**
     * @param AssignCoalesce $node
     */
    public function refactor(Node $node): Assign
    {
        return new Assign($node->var, new Coalesce($node->var, $node->expr));
    }
}
