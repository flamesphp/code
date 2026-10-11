<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rules\Php71\ValueObject\TwoNodeMatch;
use Flames\Code\Upgrade\PhpParser\Node\AssignAndBinaryMap;
final readonly class BinaryOpManipulator
{
    public function __construct(private AssignAndBinaryMap $assignAndBinaryMap)
    {
    }
    /**
     * Tries to match left or right parts (xor),
     * returns null or match on first condition and then second condition. No matter what the origin order is.
     *
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $firstCondition
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $secondCondition
     */
    public function matchFirstAndSecondConditionNode(BinaryOp $binaryOp, $firstCondition, $secondCondition): ?TwoNodeMatch
    {
        $this->validateCondition($firstCondition);
        $this->validateCondition($secondCondition);
        $firstCondition = $this->normalizeCondition($firstCondition);
        $secondCondition = $this->normalizeCondition($secondCondition);
        if ($firstCondition($binaryOp->left, $binaryOp->right) && $secondCondition($binaryOp->right, $binaryOp->left)) {
            return new TwoNodeMatch($binaryOp->left, $binaryOp->right);
        }
        if (!$firstCondition($binaryOp->right, $binaryOp->left)) {
            return null;
        }
        if (!$secondCondition($binaryOp->left, $binaryOp->right)) {
            return null;
        }
        return new TwoNodeMatch($binaryOp->right, $binaryOp->left);
    }
    public function inverseBooleanOr(BooleanOr $booleanOr): ?BinaryOp
    {
        // no nesting
        if ($booleanOr->left instanceof BooleanOr) {
            return null;
        }
        if ($booleanOr->right instanceof BooleanOr) {
            return null;
        }
        $inversedNodeClass = $this->resolveInversedNodeClass($booleanOr);
        if ($inversedNodeClass === null) {
            return null;
        }
        $firstInversedExpr = $this->inverseNode($booleanOr->left);
        $secondInversedExpr = $this->inverseNode($booleanOr->right);
        return new $inversedNodeClass($firstInversedExpr, $secondInversedExpr);
    }
    public function inverseBooleanAnd(BooleanAnd $booleanAnd): ?BinaryOp
    {
        // no nesting
        if ($booleanAnd->left instanceof BooleanAnd) {
            return null;
        }
        if ($booleanAnd->right instanceof BooleanAnd) {
            return null;
        }
        $firstInversedExpr = $this->inverseNode($booleanAnd->left);
        $secondInversedExpr = $this->inverseNode($booleanAnd->right);
        return new BooleanOr($firstInversedExpr, $secondInversedExpr);
    }
    public function invertCondition(BinaryOp $binaryOp): ?BinaryOp
    {
        // no nesting
        if ($binaryOp->left instanceof BooleanOr) {
            return null;
        }
        if ($binaryOp->right instanceof BooleanOr) {
            return null;
        }
        $inversedNodeClass = $this->resolveInversedNodeClass($binaryOp);
        if ($inversedNodeClass === null) {
            return null;
        }
        return new $inversedNodeClass($binaryOp->left, $binaryOp->right);
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot
     */
    public function inverseNode(Expr $expr)
    {
        if ($expr instanceof BinaryOp) {
            $inversedBinaryOp = $this->assignAndBinaryMap->getInversed($expr);
            if ($inversedBinaryOp !== null) {
                return new $inversedBinaryOp($expr->left, $expr->right);
            }
        }
        if ($expr instanceof BooleanNot) {
            return $expr->expr;
        }
        return new BooleanNot($expr);
    }
    /**
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $firstCondition
     */
    private function validateCondition($firstCondition): void
    {
        if (is_callable($firstCondition)) {
            return;
        }
        if (is_a($firstCondition, Node::class, \true)) {
            return;
        }
        throw new ShouldNotHappenException();
    }
    /**
     * @param callable(Node $firstNode, Node $secondNode): bool|class-string<Node> $condition
     * @return callable(Node $firstNode, Node $secondNode): bool
     */
    private function normalizeCondition($condition): callable
    {
        if (is_callable($condition)) {
            return $condition;
        }
        return static fn(Node $node): bool => $node instanceof $condition;
    }
    /**
     * @return class-string<BinaryOp>|null
     */
    private function resolveInversedNodeClass(BinaryOp $binaryOp): ?string
    {
        $inversedNodeClass = $this->assignAndBinaryMap->getInversed($binaryOp);
        if ($inversedNodeClass !== null) {
            return $inversedNodeClass;
        }
        if ($binaryOp instanceof BooleanOr) {
            return BooleanAnd::class;
        }
        return null;
    }
}
