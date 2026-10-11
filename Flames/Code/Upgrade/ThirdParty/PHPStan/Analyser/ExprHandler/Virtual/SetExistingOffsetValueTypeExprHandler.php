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
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<SetExistingOffsetValueTypeExpr>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/SetExistingOffsetValueTypeExprHandler.cpp')]
final class SetExistingOffsetValueTypeExprHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    public function __construct(ExpressionResultFactory $expressionResultFactory)
    {
        $this->expressionResultFactory = $expressionResultFactory;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof SetExistingOffsetValueTypeExpr;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        // virtual node: callers only read the type, computed lazily by the
        // typeCallback. The (synthetic) sub-expressions are processed here - by
        // on-demand time their real leaves are already stored, so this reads them
        // back; the typeCallback then reads the ExpressionResults instead of
        // Scope::getType(). A null specifyTypesCallback falls back to default
        // narrowing in TypeSpecifier, matching the old specifyDefaultTypes().
        $varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context);
        $dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context);
        $valueResult = $nodeScopeResolver->processExprNode($stmt, $expr->value, $scope, $storage, $nodeCallback, $context);
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], static fn(bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType())->setExistingOffsetValueType($nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType(), $nativeTypesPromoted ? $valueResult->getNativeType() : $valueResult->getType()), SpecifiedTypes::emptySpecifyCallback(), \false, \null, \null, \null, \null, \null, \null, \null, \null, Dependencies::merge($varResult->getDependencies(), $dimResult->getDependencies(), $valueResult->getDependencies()));
    }
}
