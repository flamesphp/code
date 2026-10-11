<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
#[AutowiredService]
final class IdateFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private \PHPStan\Type\Php\IdateFunctionReturnTypeHelper $idateFunctionReturnTypeHelper;
    public function __construct(\PHPStan\Type\Php\IdateFunctionReturnTypeHelper $idateFunctionReturnTypeHelper)
    {
        $this->idateFunctionReturnTypeHelper = $idateFunctionReturnTypeHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'idate';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if ($args === []) {
            return new ConstantBooleanType(\false);
        }
        return $this->idateFunctionReturnTypeHelper->getTypeFromFormatType($scope->getType($args[0]->value));
    }
}
