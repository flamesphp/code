<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\DowngradePhp80\NodeAnalyzer\UnnamedArgumentResolver;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\MethodCall\DowngradeNamedArgumentRectorTest
 */
final class DowngradeNamedArgumentRector extends AbstractRector
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly UnnamedArgumentResolver $unnamedArgumentResolver, private readonly ArgsAnalyzer $argsAnalyzer)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class, New_::class, FuncCall::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove named argument', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $this->execute(b: 100);
    }

    private function execute($a = null, $b = null)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $this->execute(null, 100);
    }

    private function execute($a = null, $b = null)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param MethodCall|StaticCall|New_|FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        $args = $node->getArgs();
        if (!$this->argsAnalyzer->hasNamedArg($args)) {
            return null;
        }
        return $this->removeNamedArguments($node, $args);
    }
    /**
     * @param Arg[] $args
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $node
     */
    private function removeNamedArguments($node, array $args): ?Node
    {
        if ($node instanceof New_) {
            $functionLikeReflection = $this->reflectionResolver->resolveMethodReflectionFromNew($node);
        } else {
            $functionLikeReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        }
        if (!$functionLikeReflection instanceof MethodReflection && !$functionLikeReflection instanceof FunctionReflection) {
            // remove leftovers in case of unknown type, to avoid crashing on unknown syntax
            if ($node instanceof MethodCall) {
                $callerType = $this->getType($node->var);
                if ($callerType instanceof MixedType) {
                    foreach ($node->getArgs() as $arg) {
                        if ($arg->name instanceof Node) {
                            $arg->name = null;
                        }
                    }
                    return $node;
                }
            }
            return null;
        }
        $node->args = $this->unnamedArgumentResolver->resolveFromReflection($functionLikeReflection, $args);
        return $node;
    }
}
