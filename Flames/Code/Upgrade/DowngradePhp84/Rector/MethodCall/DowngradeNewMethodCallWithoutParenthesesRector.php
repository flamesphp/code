<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp84\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/new_without_parentheses
 *
 * @see \Flames\Code\Upgrade\DowngradePhp84\Rector\MethodCall\DowngradeNewMethodCallWithoutParenthesesRectorTest
 */
final class DowngradeNewMethodCallWithoutParenthesesRector extends AbstractRector
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
        return new RuleDefinition('Add parentheses on new method call without parentheses', [new CodeSample(<<<'CODE_SAMPLE'
new Request()->withMethod('GET')->withUri('/hello-world');
CODE_SAMPLE
, <<<'CODE_SAMPLE'
(new Request())->withMethod('GET')->withUri('/hello-world');
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
        $oldTokens = $this->file->getOldTokens();
        $startTokenPos = $node->getStartTokenPos();
        $endTokenPos = $node->getEndTokenPos();
        if (!isset($oldTokens[$startTokenPos], $oldTokens[$endTokenPos])) {
            return null;
        }
        if ((string) $oldTokens[$node->getStartTokenPos()] === '(') {
            return null;
        }
        $oldTokens[$node->var->getStartTokenPos()]->text = '(' . $oldTokens[$node->var->getStartTokenPos()];
        $oldTokens[$node->var->getEndTokenPos()]->text .= ')';
        return $node;
    }
}
