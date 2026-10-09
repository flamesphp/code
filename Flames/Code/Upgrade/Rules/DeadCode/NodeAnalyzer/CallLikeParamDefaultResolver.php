<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\NullType;
use Flames\Code\Upgrade\NodeAnalyzer\VariadicAnalyzer;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
final readonly class CallLikeParamDefaultResolver
{
    public function __construct(private ReflectionResolver $reflectionResolver, private VariadicAnalyzer $variadicAnalyzer)
    {
    }
    /**
     * @return int[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $callLike
     */
    public function resolveNullPositions($callLike): array
    {
        $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($callLike);
        if (!$reflection instanceof MethodReflection && !$reflection instanceof FunctionReflection) {
            return [];
        }
        if ($reflection instanceof MethodReflection && $reflection->getName() === 'get') {
            $classReflection = $reflection->getDeclaringClass();
            if ($classReflection->getName() === \Ds\Map::class) {
                return [];
            }
        }
        if ($this->variadicAnalyzer->hasVariadicParameters($callLike)) {
            return [];
        }
        $nullPositions = [];
        $extendedParametersAcceptor = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants());
        foreach ($extendedParametersAcceptor->getParameters() as $position => $extendedParameterReflection) {
            if (!$extendedParameterReflection->getDefaultValue() instanceof NullType) {
                continue;
            }
            $nullPositions[] = $position;
        }
        return $nullPositions;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $callLike
     */
    public function resolvePositionParameterByName($callLike, string $parameterName): ?int
    {
        $reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($callLike);
        if (!$reflection instanceof MethodReflection && !$reflection instanceof FunctionReflection) {
            return null;
        }
        $extendedParametersAcceptor = ParametersAcceptorSelector::combineAcceptors($reflection->getVariants());
        foreach ($extendedParametersAcceptor->getParameters() as $position => $extendedParameterReflection) {
            if ($extendedParameterReflection->getName() === $parameterName) {
                return $position;
            }
        }
        return null;
    }
}
