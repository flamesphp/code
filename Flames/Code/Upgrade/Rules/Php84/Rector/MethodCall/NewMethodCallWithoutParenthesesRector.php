<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRectorTest
 */
final class NewMethodCallWithoutParenthesesRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove parentheses on new method call with parentheses', [new CodeSample(<<<'CODE_SAMPLE'
(new Request())->withMethod('GET')->withUri('/hello-world');
CODE_SAMPLE
, <<<'CODE_SAMPLE'
new Request()->withMethod('GET')->withUri('/hello-world');
CODE_SAMPLE
)]);
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->var instanceof New_) {
            return null;
        }
        $oldTokens = $this->getFile()->getOldTokens();
        $loop = 1;
        while (isset($oldTokens[$node->var->getStartTokenPos() + $loop])) {
            if (trim((string) $oldTokens[$node->var->getStartTokenPos() + $loop]) === '') {
                ++$loop;
                continue;
            }
            if ((string) $oldTokens[$node->var->getStartTokenPos() + $loop] !== '(') {
                break;
            }
            return null;
        }
        // start node
        if (!isset($oldTokens[$node->getStartTokenPos()])) {
            return null;
        }
        // end of "var" node
        if (!isset($oldTokens[$node->var->getEndTokenPos()])) {
            return null;
        }
        if ((string) $oldTokens[$node->getStartTokenPos()] === '(' && (string) $oldTokens[$node->var->getEndTokenPos()] === ')') {
            $oldTokens[$node->getStartTokenPos()]->text = '';
            $oldTokens[$node->var->getEndTokenPos()]->text = '';
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NEW_METHOD_CALL_WITHOUT_PARENTHESES;
    }
}
