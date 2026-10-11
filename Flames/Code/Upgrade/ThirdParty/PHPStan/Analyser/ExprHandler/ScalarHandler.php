<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * @implements ExprHandler<Scalar>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/ScalarHandler.cpp')]
final class ScalarHandler implements ExprHandler
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ExpressionResultFactory $expressionResultFactory;
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ExpressionResultFactory $expressionResultFactory)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->expressionResultFactory = $expressionResultFactory;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof Scalar && !$expr instanceof InterpolatedString;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        // a literal's type and its initializer context (file/namespace/class) are
        // lexical - identical on every scope - so build the context once here.
        $initializerExprContext = InitializerExprContext::fromScope($scope);
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], fn() => $this->initializerExprTypeResolver->getType($expr, $initializerExprContext), SpecifiedTypes::emptySpecifyCallback());
    }
}
