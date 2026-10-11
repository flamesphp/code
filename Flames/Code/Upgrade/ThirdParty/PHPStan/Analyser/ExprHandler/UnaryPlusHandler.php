<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<UnaryPlus>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/UnaryPlusHandler.cpp')]
final class UnaryPlusHandler implements ExprHandler
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof UnaryPlus;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
        return $this->expressionResultFactory->create($exprResult->getScope(), $scope, $expr, $exprResult->hasYield(), $exprResult->isAlwaysTerminating(), $exprResult->getThrowPoints(), $exprResult->getImpurePoints(), fn(bool $nativeTypesPromoted) => $this->initializerExprTypeResolver->getUnaryPlusType($expr->expr, static function (Expr $e) use ($nativeTypesPromoted, $expr, $exprResult): Type {
            if ($e === $expr->expr) {
                return $nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType();
            }
            throw new ShouldNotHappenException();
        }), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, $exprResult->getVariableFlow(), $exprResult->getDependencies());
    }
}
