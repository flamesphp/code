<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/exit-as-function
 *
 * @see \Flames\Code\Upgrade\DowngradePhp84\Rector\FuncCall\DowngradeExitNamedArgumentRectorTest
 */
final class DowngradeExitNamedArgumentRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove named argument from exit() and die()', [new CodeSample(<<<'CODE_SAMPLE'
exit(status: 1);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
exit(1);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isNames($node, ['exit', 'die'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 1) {
            return null;
        }
        $statusArg = $args[0];
        if (!$statusArg instanceof Arg) {
            return null;
        }
        if (!$statusArg->name instanceof Identifier) {
            return null;
        }
        $statusArg->name = null;
        return $node;
    }
}
