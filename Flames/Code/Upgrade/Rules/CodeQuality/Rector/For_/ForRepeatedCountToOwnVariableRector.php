<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\For_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\For_;
use Flames\Code\Upgrade\Contract\Rector\HTMLAverseRectorInterface;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRectorTest
 */
final class ForRepeatedCountToOwnVariableRector extends AbstractRector implements HTMLAverseRectorInterface
{
    private const string COUNTER_NAME = 'counter';
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change count() in for function to own variable', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($items)
    {
        for ($i = 5; $i <= count($items); $i++) {
            echo $items[$i];
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($items)
    {
        $itemsCount = count($items);
        for ($i = 5; $i <= $itemsCount; $i++) {
            echo $items[$i];
        }
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [For_::class];
    }
    /**
     * @param For_ $node
     * @return Stmt[]|null
     */
    public function refactor(Node $node): ?array
    {
        $scope = ScopeFetcher::fetch($node);
        if ($scope->hasVariableType(self::COUNTER_NAME)->yes()) {
            return null;
        }
        $countInCond = null;
        $counterVariable = new Variable(self::COUNTER_NAME);
        foreach ($node->cond as $condExpr) {
            if (!$condExpr instanceof Smaller && !$condExpr instanceof SmallerOrEqual) {
                continue;
            }
            if (!$condExpr->right instanceof FuncCall) {
                continue;
            }
            $funcCall = $condExpr->right;
            if (!$this->isName($funcCall, 'count')) {
                continue;
            }
            $countInCond = $condExpr->right;
            $condExpr->right = $counterVariable;
        }
        if (!$countInCond instanceof Expr) {
            return null;
        }
        $countAssign = new Assign($counterVariable, $countInCond);
        return [new Expression($countAssign), $node];
    }
}
