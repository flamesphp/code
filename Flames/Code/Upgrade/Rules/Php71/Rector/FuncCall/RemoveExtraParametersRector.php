<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php71\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Reflection\Annotations\AnnotationMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\Type\UnionTypeMethodReflection;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeAnalyzer\VariadicAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php71\Rector\FuncCall\RemoveExtraParametersRectorTest
 */
final class RemoveExtraParametersRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly VariadicAnalyzer $variadicAnalyzer, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_EXTRA_PARAMETERS;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove extra parameters', [new CodeSample('strlen("asdf", 1);', 'strlen("asdf");')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, MethodCall::class, StaticCall::class];
    }
    /**
     * @param FuncCall|MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        // unreliable count of arguments
        $functionLikeReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        if ($functionLikeReflection instanceof UnionTypeMethodReflection) {
            return null;
        }
        if ($functionLikeReflection === null) {
            return null;
        }
        // magic @method has no real parameters to compare against
        if ($functionLikeReflection instanceof AnnotationMethodReflection) {
            return null;
        }
        if ($functionLikeReflection instanceof PhpMethodReflection) {
            if ($functionLikeReflection->isAbstract()) {
                return null;
            }
            $classReflection = $functionLikeReflection->getDeclaringClass();
            if ($classReflection->isInterface()) {
                return null;
            }
        }
        $maximumAllowedParameterCount = $this->resolveMaximumAllowedParameterCount($functionLikeReflection);
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if ($this->shouldSkipFunctionReflection($functionLikeReflection)) {
            return null;
        }
        $numberOfArguments = count($node->getRawArgs());
        if ($numberOfArguments <= $maximumAllowedParameterCount) {
            return null;
        }
        for ($i = $maximumAllowedParameterCount; $i <= $numberOfArguments; ++$i) {
            unset($node->args[$i]);
        }
        return $node;
    }
    /**
     * @param \PHPStan\Reflection\MethodReflection|\PHPStan\Reflection\FunctionReflection $reflection
     */
    private function shouldSkipFunctionReflection($reflection): bool
    {
        if ($reflection instanceof FunctionReflection) {
            $fileName = (string) $reflection->getFileName();
            if (str_contains($fileName, 'phpstan.phar')) {
                return \true;
            }
        }
        if ($reflection instanceof MethodReflection) {
            $classReflection = $reflection->getDeclaringClass();
            $fileName = (string) $classReflection->getFileName();
            if (str_contains($fileName, 'phpstan.phar')) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $call
     */
    private function shouldSkip($call): bool
    {
        if ($call->args === []) {
            return \true;
        }
        if ($call instanceof StaticCall) {
            if (!$call->class instanceof Name) {
                return \true;
            }
            if ($this->isName($call->class, ObjectReference::PARENT)) {
                return \true;
            }
        }
        return $this->variadicAnalyzer->hasVariadicParameters($call);
    }
    /**
     * @param \PHPStan\Reflection\MethodReflection|\PHPStan\Reflection\FunctionReflection $functionLikeReflection
     */
    private function resolveMaximumAllowedParameterCount($functionLikeReflection): int
    {
        $parameterCounts = [0];
        foreach ($functionLikeReflection->getVariants() as $parametersAcceptor) {
            $parameterCounts[] = count($parametersAcceptor->getParameters());
        }
        // empty variants -> use max value possibly has to prevent removing arguments incorrectly
        if ($parameterCounts === [0]) {
            return \PHP_INT_MAX;
        }
        return max($parameterCounts);
    }
}
