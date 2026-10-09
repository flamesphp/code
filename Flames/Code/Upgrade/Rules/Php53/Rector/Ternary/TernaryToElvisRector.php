<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php53\Rector\Ternary;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Ternary;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php53\Rector\Ternary\TernaryToElvisRectorTest
 */
final class TernaryToElvisRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use `?:` instead of `?`, where useful', [new CodeSample(<<<'CODE_SAMPLE'
function elvis()
{
    $value = $a ? $a : false;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function elvis()
{
    $value = $a ?: false;
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
        if (!$this->nodeComparator->areNodesEqual($node->cond, $node->if)) {
            return null;
        }
        $node->setAttribute(AttributeKey::ORIGINAL_NODE, null);
        /** @var Expr $nodeIf */
        $nodeIf = $node->if;
        if ($node->else instanceof Ternary && $this->isParenthesized($nodeIf, $node->else)) {
            $node->else->setAttribute(AttributeKey::WRAPPED_IN_PARENTHESES, \true);
        }
        $node->if = null;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ELVIS_OPERATOR;
    }
    private function isParenthesized(Expr $ifExpr, Expr $elseExpr): bool
    {
        $tokens = $this->getFile()->getOldTokens();
        $ifExprTokenEnd = $ifExpr->getEndTokenPos();
        $elseExprTokenStart = $elseExpr->getStartTokenPos();
        if ($ifExprTokenEnd < 0 || $elseExprTokenStart < 0 || $elseExprTokenStart <= $ifExprTokenEnd) {
            return \false;
        }
        while (isset($tokens[$ifExprTokenEnd])) {
            ++$ifExprTokenEnd;
            if ($elseExprTokenStart === $ifExprTokenEnd) {
                break;
            }
            if ((string) $tokens[$ifExprTokenEnd] === '(') {
                return \true;
            }
        }
        return \false;
    }
}
