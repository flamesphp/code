<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Concat;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Concat\RemoveConcatAutocastRectorTest
 */
final class RemoveConcatAutocastRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove (string) casting when it comes to concat, that does this by default', [new CodeSample(<<<'CODE_SAMPLE'
class SomeConcatenatingClass
{
    public function run($value)
    {
        return 'hi ' . (string) $value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeConcatenatingClass
{
    public function run($value)
    {
        return 'hi ' . $value;
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
        return [Concat::class];
    }
    /**
     * @param Concat $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->left instanceof String_ && !$node->right instanceof String_) {
            return null;
        }
        $node->left = $this->removeStringCast($node->left);
        $node->right = $this->removeStringCast($node->right);
        return $node;
    }
    private function removeStringCast(Expr $expr): Expr
    {
        if (!$expr instanceof String_) {
            return $expr;
        }
        $targetExpr = $expr->expr;
        $tokens = $this->getFile()->getOldTokens();
        if ($expr->expr instanceof BinaryOp) {
            $castStartTokenPos = $expr->getStartTokenPos();
            $targetExprStartTokenPos = $targetExpr->getStartTokenPos();
            while (++$castStartTokenPos < $targetExprStartTokenPos) {
                if (isset($tokens[$castStartTokenPos]) && (string) $tokens[$castStartTokenPos] === '(') {
                    $targetExpr->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \true);
                    break;
                }
            }
        }
        return $targetExpr;
    }
}
