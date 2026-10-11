<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * Builds ExpressionResults for virtual nodes whose answer is available without
 * a walk - the types they carry, or the already-processed results they compose.
 * The virtual node handlers delegate here, so a fabricated result and a walked
 * one are identical by construction.
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/VirtualExprResultHelper.cpp')]
final class VirtualExprResultHelper
{
    private ExpressionResultFactory $expressionResultFactory;
    private \PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper $defaultNarrowingHelper;
    public function __construct(ExpressionResultFactory $expressionResultFactory, \PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper $defaultNarrowingHelper)
    {
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
    }
    /**
     * @param \PHPStan\Node\Expr\TypeExpr|\PHPStan\Node\Expr\NativeTypeExpr $expr
     */
    public function createTypeExprResult(MutatingScope $scope, $expr): ExpressionResult
    {
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], $expr instanceof TypeExpr ? static fn(bool $nativeTypesPromoted): Type => $expr->exprType : static fn(bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $expr->nativeType : $expr->phpdocType, fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context));
    }
    public function createUnsetOffsetExprResult(MutatingScope $scope, UnsetOffsetExpr $expr, ExpressionResult $varResult, ExpressionResult $dimResult): ExpressionResult
    {
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], static fn(bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType())->unsetOffset($nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType()), SpecifiedTypes::emptySpecifyCallback());
    }
}
