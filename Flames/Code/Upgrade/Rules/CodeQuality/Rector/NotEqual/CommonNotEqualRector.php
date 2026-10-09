<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\NotEqual;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\NotEqual\CommonNotEqualRectorTest
 */
final class CommonNotEqualRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use common != instead of less known <> with same meaning', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($one, $two)
    {
        return $one <> $two;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($one, $two)
    {
        return $one != $two;
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
        return [NotEqual::class];
    }
    /**
     * @param NotEqual $node
     */
    public function refactor(Node $node): ?NotEqual
    {
        $tokenStartPos = $node->getStartTokenPos();
        $tokenEndPos = $node->getEndTokenPos();
        for ($i = $tokenStartPos; $i < $tokenEndPos; ++$i) {
            $token = $this->getFile()->getOldTokens()[$i];
            if ((string) $token === '<>') {
                $token->text = '!=';
                return $node;
            }
        }
        return null;
    }
}
