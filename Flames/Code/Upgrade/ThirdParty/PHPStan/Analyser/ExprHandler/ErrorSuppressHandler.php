<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<ErrorSuppress>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/ErrorSuppressHandler.cpp')]
final class ErrorSuppressHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    public function __construct(ExpressionResultFactory $expressionResultFactory)
    {
        $this->expressionResultFactory = $expressionResultFactory;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof ErrorSuppress;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context);
        return $this->expressionResultFactory->create($exprResult->getScope(), $beforeScope, $expr, $exprResult->hasYield(), $exprResult->isAlwaysTerminating(), $exprResult->getThrowPoints(), $exprResult->getImpurePoints(), static fn(bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType(), static fn(TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $exprResult->getSpecifiedTypes($context, $nativeTypesPromoted)->setRootExpr($expr), \false, \null, \null, \null, \null, \null, \null, \null, $exprResult->getVariableFlow(), $exprResult->getDependencies());
    }
}
