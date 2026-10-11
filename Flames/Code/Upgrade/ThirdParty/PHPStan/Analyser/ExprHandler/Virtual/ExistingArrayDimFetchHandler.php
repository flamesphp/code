<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
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
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<ExistingArrayDimFetch>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/ExistingArrayDimFetchHandler.cpp')]
final class ExistingArrayDimFetchHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    public function __construct(ExpressionResultFactory $expressionResultFactory)
    {
        $this->expressionResultFactory = $expressionResultFactory;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof ExistingArrayDimFetch;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        // virtual node: callers only read the type, computed lazily by the
        // typeCallback. The plain array dim fetch is processed here (its real
        // leaves are already stored by on-demand time) so the typeCallback reads
        // its ExpressionResult instead of Scope::getType(). A null
        // specifyTypesCallback falls back to default narrowing in TypeSpecifier,
        // matching the old specifyDefaultTypes().
        $arrayDimFetchResult = $nodeScopeResolver->processExprNode($stmt, new Expr\ArrayDimFetch($expr->var, $expr->dim), $scope, $storage, $nodeCallback, $context);
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], static fn(bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $arrayDimFetchResult->getNativeType() : $arrayDimFetchResult->getType(), SpecifiedTypes::emptySpecifyCallback(), \false, \null, \null, \null, \null, \null, \null, \null, \null, $arrayDimFetchResult->getDependencies());
    }
}
