<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\Rules\CodingStyle\NodeFactory\ArrayCallableToMethodCallFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRectorTest
 */
final class CallUserFuncToMethodCallRector extends AbstractRector
{
    public function __construct(private readonly ArrayCallableToMethodCallFactory $arrayCallableToMethodCallFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Refactor `call_user_func()` on known class method to a method call', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $result = \call_user_func([$this->property, 'method'], $args);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $result = $this->property->method($args);
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
        if (!$this->isName($node, 'call_user_func')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!isset($node->getArgs()[0])) {
            return null;
        }
        $firstArgValue = $node->getArgs()[0]->value;
        if (!$firstArgValue instanceof Array_) {
            return null;
        }
        // remove first arg
        $originalArgs = $node->getArgs();
        array_shift($originalArgs);
        $methodCall = $this->arrayCallableToMethodCallFactory->create($firstArgValue);
        if (!$methodCall instanceof MethodCall) {
            return null;
        }
        $methodCall->args = $originalArgs;
        return $methodCall;
    }
}
