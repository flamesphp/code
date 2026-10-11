<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp86\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/clamp_v2
 * @see \Flames\Code\Upgrade\DowngradePhp86\Rector\FuncCall\DowngradeClampRectorTest
 */
final class DowngradeClampRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace clamp() with min()/max().', [new CodeSample(<<<'CODE_SAMPLE'
clamp($value, $min, $max);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
max($min, min($max, $value));
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node, 'clamp')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $valueArg = $node->getArg('value', 0);
        $minArg = $node->getArg('min', 1);
        $maxArg = $node->getArg('max', 2);
        if (!$valueArg instanceof Arg || !$minArg instanceof Arg || !$maxArg instanceof Arg) {
            return null;
        }
        return $this->nodeFactory->createFuncCall('max', [$minArg->value, $this->nodeFactory->createFuncCall('min', [$maxArg->value, $valueArg->value])]);
    }
}
