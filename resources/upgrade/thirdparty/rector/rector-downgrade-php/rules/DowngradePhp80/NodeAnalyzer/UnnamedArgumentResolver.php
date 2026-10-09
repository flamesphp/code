<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\NodeAnalyzer;

use PhpParser\Node\Arg;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use ReflectionFunction;
final readonly class UnnamedArgumentResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private \Flames\Code\Upgrade\DowngradePhp80\NodeAnalyzer\NamedToUnnamedArgs $namedToUnnamedArgs)
    {
    }
    /**
     * @param Arg[] $currentArgs
     * @return Arg[]
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection $functionLikeReflection
     */
    public function resolveFromReflection($functionLikeReflection, array $currentArgs): array
    {
        $extendedParametersAcceptor = ParametersAcceptorSelector::combineAcceptors($functionLikeReflection->getVariants());
        $parameters = $extendedParametersAcceptor->getParameters();
        if ($functionLikeReflection instanceof NativeFunctionReflection) {
            $functionLikeReflection = new ReflectionFunction($functionLikeReflection->getName());
        }
        /** @var array<int, Arg> $unnamedArgs */
        $unnamedArgs = [];
        $toFillArgs = [];
        foreach ($currentArgs as $key => $arg) {
            if (!$arg->name instanceof Identifier) {
                $unnamedArgs[$key] = new Arg($arg->value, $arg->byRef, $arg->unpack, []);
                continue;
            }
            /** @var string $argName */
            $argName = $this->nodeNameResolver->getName($arg->name);
            $toFillArgs[] = $argName;
        }
        $unnamedArgs = $this->namedToUnnamedArgs->fillFromNamedArgs($parameters, $currentArgs, $toFillArgs, $unnamedArgs);
        $unnamedArgs = $this->namedToUnnamedArgs->fillFromJumpedNamedArgs($functionLikeReflection, $unnamedArgs, $parameters);
        ksort($unnamedArgs);
        return $unnamedArgs;
    }
}
