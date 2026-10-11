<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanAnd;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\Rules\CodeQuality\NodeFactory\InArrayFromRepeatedCompareFactory;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\ComparedExprAndValueExpr;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanAnd\RepeatedAndNotEqualToNotInArrayRectorTest
 */
final class RepeatedAndNotEqualToNotInArrayRector extends AbstractRector
{
    public function __construct(private readonly BetterNodeFinder $betterNodeFinder, private readonly InArrayFromRepeatedCompareFactory $inArrayFromRepeatedCompareFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Simplify repeated && compare of same value, to ! in_array() call', [new CodeSample(<<<'CODE_SAMPLE'
if ($value !== 10 && $value !== 20 && $value !== 30) {
    // ...
}

CODE_SAMPLE
, <<<'CODE_SAMPLE'
if (! in_array($value, [10, 20, 30], true)) {
    // ...
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanAnd::class];
    }
    /**
     * @param BooleanAnd $node
     */
    public function refactor(Node $node): ?BooleanNot
    {
        if (!$this->isNotEqualOrNotIdentical($node->right)) {
            return null;
        }
        // match compared variable and expr
        if (!$node->left instanceof BooleanAnd && !$this->isNotEqualOrNotIdentical($node->left)) {
            return null;
        }
        $comparedExprAndValueExprs = $this->matchComparedAndDesiredValues($node);
        if ($comparedExprAndValueExprs === null) {
            return null;
        }
        $args = $this->inArrayFromRepeatedCompareFactory->createInArrayArgs($comparedExprAndValueExprs);
        if ($args === null) {
            return null;
        }
        if ($this->isStrictComparison($node)) {
            $args[] = new Arg(new ConstFetch(new Name('true')));
        }
        $inArrayFuncCall = new FuncCall(new Name('in_array'), $args);
        return new BooleanNot($inArrayFuncCall);
    }
    private function isNotEqualOrNotIdentical(Expr $expr): bool
    {
        if ($expr instanceof NotIdentical) {
            return \true;
        }
        return $expr instanceof NotEqual;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual $expr
     */
    private function matchComparedExprAndValueExpr($expr): ComparedExprAndValueExpr
    {
        return new ComparedExprAndValueExpr($expr->left, $expr->right);
    }
    /**
     * @return null|ComparedExprAndValueExpr[]
     */
    private function matchComparedAndDesiredValues(BooleanAnd $booleanAnd): ?array
    {
        /** @var NotIdentical|NotEqual $rightCompare */
        $rightCompare = $booleanAnd->right;
        // match compared expr and desired value
        $comparedExprAndValueExprs = [$this->matchComparedExprAndValueExpr($rightCompare)];
        $currentBooleanAnd = $booleanAnd;
        while ($currentBooleanAnd->left instanceof BooleanAnd) {
            if (!$this->isNotEqualOrNotIdentical($currentBooleanAnd->left->right)) {
                return null;
            }
            /** @var NotIdentical|NotEqual $leftRight */
            $leftRight = $currentBooleanAnd->left->right;
            $comparedExprAndValueExprs[] = $this->matchComparedExprAndValueExpr($leftRight);
            $currentBooleanAnd = $currentBooleanAnd->left;
        }
        if (!$this->isNotEqualOrNotIdentical($currentBooleanAnd->left)) {
            return null;
        }
        /** @var NotIdentical|NotEqual $leftCompare */
        $leftCompare = $currentBooleanAnd->left;
        $comparedExprAndValueExprs[] = $this->matchComparedExprAndValueExpr($leftCompare);
        // keep original natural order, as left/right goes from bottom up
        return array_reverse($comparedExprAndValueExprs);
    }
    private function isStrictComparison(BooleanAnd $booleanAnd): bool
    {
        $notIdenticals = $this->betterNodeFinder->findInstanceOf($booleanAnd, NotIdentical::class);
        $notEquals = $this->betterNodeFinder->findInstanceOf($booleanAnd, NotEqual::class);
        if ($notIdenticals !== []) {
            // mix not identical and not equals, keep as is
            // @see https://3v4l.org/2SoHZ
            return $notEquals === [];
        }
        return \false;
    }
}
