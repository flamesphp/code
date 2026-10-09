<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Rules\CodingStyle\NodeFactory\ArrayCallableToMethodCallFactory;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRectorTest
 */
final class CallUserFuncArrayToVariadicRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ArrayCallableToMethodCallFactory $arrayCallableToMethodCallFactory, private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace `call_user_func_array()` with variadic', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        call_user_func_array('some_function', $items);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        some_function(...$items);
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
        if (!$this->isName($node, 'call_user_func_array')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArgValue = $node->getArgs()[0]->value;
        $secondArgValue = $node->getArgs()[1]->value;
        if ($firstArgValue instanceof String_) {
            $functionName = $this->valueResolver->getValue($firstArgValue);
            return $this->createFuncCall($secondArgValue, $functionName);
        }
        // method call
        if ($firstArgValue instanceof Array_) {
            return $this->createMethodCall($firstArgValue, $secondArgValue);
        }
        if ($firstArgValue instanceof MethodCall && $firstArgValue->isFirstClassCallable()) {
            $firstArgValue->args = [$this->createUnpackedArg($secondArgValue)];
            return $firstArgValue;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARRAY_SPREAD;
    }
    private function createFuncCall(Expr $expr, string $functionName): FuncCall
    {
        $args = [$this->createUnpackedArg($expr)];
        return $this->nodeFactory->createFuncCall($functionName, $args);
    }
    private function createMethodCall(Array_ $array, Expr $secondExpr): ?MethodCall
    {
        $methodCall = $this->arrayCallableToMethodCallFactory->create($array);
        if (!$methodCall instanceof MethodCall) {
            return null;
        }
        $methodCall->args[] = $this->createUnpackedArg($secondExpr);
        return $methodCall;
    }
    private function createUnpackedArg(Expr $expr): Arg
    {
        return new Arg($expr, \false, \true);
    }
}
