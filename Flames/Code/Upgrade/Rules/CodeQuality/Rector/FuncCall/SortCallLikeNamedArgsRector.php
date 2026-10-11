<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use Flames\Code\Upgrade\Rules\CodeQuality\NodeManipulator\NamedArgsSorter;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRectorTest
 */
final class SortCallLikeNamedArgsRector extends AbstractRector
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly ArgsAnalyzer $argsAnalyzer, private readonly NamedArgsSorter $namedArgsSorter)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Sort named arguments to match their order in a function or method call or class constructors', [new CodeSample(<<<'CODE_SAMPLE'
function run($foo = null, $bar = null, $baz = null) {}

run(bar: $bar, foo: $foo);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function run($foo = null, $bar = null, $baz = null) {}

run(foo: $foo, bar: $bar);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, New_::class, FuncCall::class];
    }
    /**
     * @param MethodCall|StaticCall|New_|FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) <= 1) {
            return null;
        }
        if (!$this->argsAnalyzer->hasNamedArg($args)) {
            return null;
        }
        if ($node instanceof New_) {
            $functionLikeReflection = $this->reflectionResolver->resolveMethodReflectionFromNew($node);
        } else {
            $functionLikeReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        }
        if (!$functionLikeReflection instanceof MethodReflection && !$functionLikeReflection instanceof FunctionReflection) {
            return null;
        }
        $args = $this->namedArgsSorter->sortArgsToMatchReflectionParameters($args, $functionLikeReflection);
        if ($node->args === $args) {
            return null;
        }
        $node->args = $args;
        return $node;
    }
}
