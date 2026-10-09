<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Carbon\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Carbon\Rector\FuncCall\TimeFuncCallToCarbonRectorTest
 */
final class TimeFuncCallToCarbonRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Convert `time()` function call to `Carbon::now()->getTimestamp()`', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $time = time();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $time = \Carbon\Carbon::now()->getTimestamp();
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
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'time')) {
            return null;
        }
        $firstClassCallable = $node->isFirstClassCallable();
        if (!$firstClassCallable && count($node->getArgs()) !== 0) {
            return null;
        }
        // create now and format()
        $nowStaticCall = new StaticCall(new FullyQualified('Carbon\Carbon'), 'now');
        $methodCall = new MethodCall($nowStaticCall, 'getTimestamp');
        if ($firstClassCallable) {
            return new ArrowFunction(['static' => \true, 'expr' => $methodCall]);
        }
        return $methodCall;
    }
}
