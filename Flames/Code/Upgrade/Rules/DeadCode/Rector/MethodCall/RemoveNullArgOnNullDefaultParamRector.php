<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\CallLikeParamDefaultResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRectorTest
 */
final class RemoveNullArgOnNullDefaultParamRector extends AbstractRector
{
    public function __construct(private readonly ValueResolver $valueResolver, private readonly CallLikeParamDefaultResolver $callLikeParamDefaultResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove default null argument, where null is already a default param value', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function call(ExternalClass $externalClass)
    {
        $externalClass->execute(null);
    }
}

class ExternalClass
{
    public function execute(?SomeClass $someClass = null)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'

class SomeClass
{
    public function call(ExternalClass $externalClass)
    {
        $externalClass->execute();
    }
}

class ExternalClass
{
    public function execute(?SomeClass $someClass = null)
    {
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, New_::class, FuncCall::class];
    }
    /**
     * @param MethodCall|StaticCall|New_|FuncCall $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|null
     */
    public function refactor(Node $node)
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if ($args === []) {
            return null;
        }
        // named args are handled by RemoveNullNamedArgOnNullDefaultParamRector
        foreach ($args as $arg) {
            if ($arg->name instanceof Identifier) {
                return null;
            }
            if ($arg->unpack) {
                return null;
            }
        }
        $nullPositions = $this->callLikeParamDefaultResolver->resolveNullPositions($node);
        if ($nullPositions === []) {
            return null;
        }
        $hasChanged = \false;
        for ($position = count($args) - 1; $position >= 0; --$position) {
            $arg = $args[$position];
            if (!$this->valueResolver->isNull($arg->value)) {
                // a named non-null argument can be skipped over: removing an earlier
                // named null argument still leaves the remaining named arguments validly bound
                if ($arg->name instanceof Identifier) {
                    continue;
                }
                break;
            }
            if (!in_array($position, $nullPositions, \true)) {
                break;
            }
            unset($node->args[$position]);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
