<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\NodeManipulator\BinaryOpManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRectorTest
 */
final class SimplifyDeMorganBinaryRector extends AbstractRector
{
    public function __construct(private readonly BinaryOpManipulator $binaryOpManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Simplify negated conditions with de Morgan theorem', [new CodeSample(<<<'CODE_SAMPLE'
$a = 5;
$b = 10;
$result = !($a > 20 || $b <= 50);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$a = 5;
$b = 10;
$result = $a <= 20 && $b > 50;
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [BooleanNot::class];
    }
    /**
     * @param BooleanNot $node
     */
    public function refactor(Node $node): ?BinaryOp
    {
        if (!$node->expr instanceof BooleanOr) {
            return null;
        }
        return $this->binaryOpManipulator->inverseBooleanOr($node->expr);
    }
}
