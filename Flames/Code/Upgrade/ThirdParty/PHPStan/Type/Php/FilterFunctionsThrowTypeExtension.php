<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
#[AutowiredService]
final class FilterFunctionsThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{
    private ReflectionProvider $reflectionProvider;
    private \PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper;
    private \PHPStan\Type\Php\FilterFunctionFlagsHelper $filterFunctionFlagsHelper;
    public function __construct(ReflectionProvider $reflectionProvider, \PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper, \PHPStan\Type\Php\FilterFunctionFlagsHelper $filterFunctionFlagsHelper)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->filterFunctionReturnTypeHelper = $filterFunctionReturnTypeHelper;
        $this->filterFunctionFlagsHelper = $filterFunctionFlagsHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $this->filterFunctionFlagsHelper->isSupported($functionReflection);
    }
    public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
    {
        if ($scope->getPhpVersion()->hasFilterThrowOnFailureConstant()->no() || !$this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null)) {
            return null;
        }
        foreach ($this->filterFunctionFlagsHelper->getFlagsTypes($functionReflection, $funcCall, $scope) as $flagsType) {
            if ($this->filterFunctionReturnTypeHelper->hasFlag('FILTER_THROW_ON_FAILURE', $flagsType)->no()) {
                continue;
            }
            return new ObjectType('Filter\FilterFailedException');
        }
        return null;
    }
}
