<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Instanceof_\Rector\Ternary;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRectorTest
 */
final class FlipNegatedTernaryInstanceofRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Flip negated ternary of `instanceof` to direct use of object', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function resolvePrice(object $object): ?int
    {
        return ! $object instanceof Product ? null : $object->getPrice();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function resolvePrice(object $object): ?int
    {
        return $object instanceof Product ? $object->getPrice() : null;
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
        return [Ternary::class];
    }
    /**
     * @param Ternary $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->if instanceof Expr) {
            return null;
        }
        if (!$node->cond instanceof BooleanNot) {
            return null;
        }
        $booleanNot = $node->cond;
        if (!$booleanNot->expr instanceof Instanceof_) {
            return null;
        }
        $node->cond = $booleanNot->expr;
        // flip if and else
        [$node->if, $node->else] = [$node->else, $node->if];
        return $node;
    }
}
