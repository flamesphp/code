<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\NodeManipulator\BinaryOpManipulator;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php71\ValueObject\TwoNodeMatch;
use Flames\Code\Upgrade\PhpParser\Node\AssignAndBinaryMap;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\SimplifyConditionsRectorTest
 */
final class SimplifyConditionsRector extends AbstractRector
{
    public function __construct(private readonly AssignAndBinaryMap $assignAndBinaryMap, private readonly BinaryOpManipulator $binaryOpManipulator, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Simplify conditions', [new CodeSample("if (! (\$foo !== 'bar')) {...", "if (\$foo === 'bar') {...")]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanNot::class, Identical::class];
    }
    /**
     * @param BooleanNot|Identical $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof BooleanNot) {
            return $this->processBooleanNot($node);
        }
        return $this->processIdenticalAndNotIdentical($node);
    }
    private function processBooleanNot(BooleanNot $booleanNot): ?Node
    {
        if (!$booleanNot->expr instanceof BinaryOp) {
            return null;
        }
        if ($this->shouldSkip($booleanNot->expr)) {
            return null;
        }
        return $this->createInversedBooleanOp($booleanNot->expr);
    }
    private function processIdenticalAndNotIdentical(Identical $identical): ?Node
    {
        $twoNodeMatch = $this->binaryOpManipulator->matchFirstAndSecondConditionNode($identical, static fn(Node $node): bool => $node instanceof Identical || $node instanceof NotIdentical, fn(Node $node): bool => $node instanceof Expr && $this->valueResolver->isTrueOrFalse($node));
        if (!$twoNodeMatch instanceof TwoNodeMatch) {
            return $twoNodeMatch;
        }
        /** @var Identical|NotIdentical $firstExpr */
        $firstExpr = $twoNodeMatch->getFirstExpr();
        $otherExpr = $twoNodeMatch->getSecondExpr();
        if ($this->valueResolver->isFalse($otherExpr)) {
            return $this->createInversedBooleanOp($firstExpr);
        }
        return $firstExpr;
    }
    /**
     * Skip too nested binary || binary > binary combinations
     */
    private function shouldSkip(BinaryOp $binaryOp): bool
    {
        if ($binaryOp instanceof BooleanOr) {
            return \true;
        }
        if ($binaryOp->left instanceof BinaryOp) {
            return \true;
        }
        return $binaryOp->right instanceof BinaryOp;
    }
    private function createInversedBooleanOp(BinaryOp $binaryOp): ?BinaryOp
    {
        $inversedBinaryClass = $this->assignAndBinaryMap->getInversed($binaryOp);
        if ($inversedBinaryClass === null) {
            return null;
        }
        if ($binaryOp->left instanceof Ternary) {
            $binaryOp->left->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \true);
        }
        if ($binaryOp->right instanceof Ternary) {
            $binaryOp->right->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \true);
        }
        return new $inversedBinaryClass($binaryOp->left, $binaryOp->right);
    }
}
