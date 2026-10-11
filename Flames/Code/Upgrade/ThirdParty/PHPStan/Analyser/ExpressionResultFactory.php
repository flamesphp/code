<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Dependency\Dependencies;
use PHPStan\Type\Type;
interface ExpressionResultFactory
{
    /**
     * @param InternalThrowPoint[] $throwPoints
     * @param ImpurePoint[] $impurePoints
     * @param (callable(bool): Type)|null $typeCallback
     * @param callable(TypeSpecifierContext, bool): SpecifiedTypes $specifyTypesCallback
     * @param (callable(Type, TypeSpecifierContext, bool): SpecifiedTypes)|null $createTypesCallback
     */
    public function create(\PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\MutatingScope $beforeScope, Expr $expr, bool $hasYield, bool $isAlwaysTerminating, array $throwPoints, array $impurePoints, ?callable $typeCallback, callable $specifyTypesCallback, bool $containsNullsafe = \false, ?\PHPStan\Analyser\IssetabilityDescriptor $issetabilityDescriptor = null, ?\PHPStan\Analyser\ExpressionResult $truthyScopeOverrideResult = null, ?\PHPStan\Analyser\ExpressionResult $falseyScopeOverrideResult = null, ?callable $createTypesCallback = null, ?Type $type = null, ?Type $nativeType = null, ?\PHPStan\Analyser\ArgsResult $argsResult = null, ?\PHPStan\Analyser\VariableFlow $variableFlow = null, ?Dependencies $dependencies = null): \PHPStan\Analyser\ExpressionResult;
}
