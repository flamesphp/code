<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRectorTest
 */
final class BooleanNotIdenticalToNotIdenticalRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Negated identical boolean compare to not identical compare (does not apply to non-bool values)', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $a = true;
        $b = false;

        var_dump(! $a === $b); // true
        var_dump(! ($a === $b)); // true
        var_dump($a !== $b); // true
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $a = true;
        $b = false;

        var_dump($a !== $b); // true
        var_dump($a !== $b); // true
        var_dump($a !== $b); // true
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
        return [Identical::class, BooleanNot::class];
    }
    /**
     * @param Identical|BooleanNot $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Identical) {
            return $this->processIdentical($node);
        }
        if ($node->expr instanceof Identical) {
            $identical = $node->expr;
            $leftType = $this->getType($identical->left);
            if (!$leftType->isBoolean()->yes()) {
                return null;
            }
            $rightType = $this->getType($identical->right);
            if (!$rightType->isBoolean()->yes()) {
                return null;
            }
            return new NotIdentical($identical->left, $identical->right);
        }
        return null;
    }
    private function processIdentical(Identical $identical): ?NotIdentical
    {
        $leftType = $this->getType($identical->left);
        if (!$leftType->isBoolean()->yes()) {
            return null;
        }
        $rightType = $this->getType($identical->right);
        if (!$rightType->isBoolean()->yes()) {
            return null;
        }
        if ($identical->left instanceof BooleanNot) {
            return new NotIdentical($identical->left->expr, $identical->right);
        }
        return null;
    }
}
