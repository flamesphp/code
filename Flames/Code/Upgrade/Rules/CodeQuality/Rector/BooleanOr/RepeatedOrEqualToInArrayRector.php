<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanOr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
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
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRectorTest
 */
final class RepeatedOrEqualToInArrayRector extends AbstractRector
{
    public function __construct(private readonly BetterNodeFinder $betterNodeFinder, private readonly InArrayFromRepeatedCompareFactory $inArrayFromRepeatedCompareFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Simplify repeated || compare of same value, to in_array() call', [new CodeSample(<<<'CODE_SAMPLE'
if ($value === 10 || $value === 20 || $value === 30) {
    // ...
}

CODE_SAMPLE
, <<<'CODE_SAMPLE'
if (in_array($value, [10, 20, 30], true)) {
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
        return [BooleanOr::class];
    }
    /**
     * @param BooleanOr $node
     */
    public function refactor(Node $node): ?FuncCall
    {
        if (!$this->isEqualOrIdentical($node->right)) {
            return null;
        }
        // match compared variable and expr
        if (!$node->left instanceof BooleanOr && !$this->isEqualOrIdentical($node->left)) {
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
        $identicals = $this->betterNodeFinder->findInstanceOf($node, Identical::class);
        $equals = $this->betterNodeFinder->findInstanceOf($node, Equal::class);
        if ($identicals !== []) {
            if ($equals !== []) {
                // mix identical and equals, keep as is
                // @see https://3v4l.org/24cFl
                return null;
            }
            $args[] = new Arg(new ConstFetch(new Name('true')));
        }
        return new FuncCall(new Name('in_array'), $args);
    }
    private function isEqualOrIdentical(Expr $expr): bool
    {
        if ($expr instanceof Identical) {
            return \true;
        }
        return $expr instanceof Equal;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal $expr
     */
    private function matchComparedExprAndValueExpr($expr): ComparedExprAndValueExpr
    {
        return new ComparedExprAndValueExpr($expr->left, $expr->right);
    }
    /**
     * @return null|ComparedExprAndValueExpr[]
     */
    private function matchComparedAndDesiredValues(BooleanOr $booleanOr): ?array
    {
        /** @var Identical|Equal $rightCompare */
        $rightCompare = $booleanOr->right;
        // match compared expr and desired value
        $comparedExprAndValueExprs = [$this->matchComparedExprAndValueExpr($rightCompare)];
        $currentBooleanOr = $booleanOr;
        while ($currentBooleanOr->left instanceof BooleanOr) {
            if (!$this->isEqualOrIdentical($currentBooleanOr->left->right)) {
                return null;
            }
            /** @var Identical|Equal $leftRight */
            $leftRight = $currentBooleanOr->left->right;
            $comparedExprAndValueExprs[] = $this->matchComparedExprAndValueExpr($leftRight);
            $currentBooleanOr = $currentBooleanOr->left;
        }
        if (!$this->isEqualOrIdentical($currentBooleanOr->left)) {
            return null;
        }
        /** @var Identical|Equal $leftCompare */
        $leftCompare = $currentBooleanOr->left;
        $comparedExprAndValueExprs[] = $this->matchComparedExprAndValueExpr($leftCompare);
        // keep original natural order, as left/right goes from bottom up
        return array_reverse($comparedExprAndValueExprs);
    }
}
