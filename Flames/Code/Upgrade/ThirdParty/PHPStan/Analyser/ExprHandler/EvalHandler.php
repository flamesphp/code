<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;
/**
 * @implements ExprHandler<Eval_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/EvalHandler.cpp')]
final class EvalHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    public function __construct(ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper)
    {
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof Eval_;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
        // the evaluated code may read any variable
        $scope = $exprResult->getScope()->invalidateVolatileExpressions();
        $throwPoint = InternalThrowPoint::createImplicit($scope, $expr);
        return $this->expressionResultFactory->create($scope, $beforeScope, $expr, $exprResult->hasYield(), $exprResult->isAlwaysTerminating(), array_merge($exprResult->getThrowPoints(), [$throwPoint]), array_merge($exprResult->getImpurePoints(), [new ImpurePoint($scope, $expr, 'eval', 'eval', \true)]), static fn(bool $nativeTypesPromoted): Type => new MixedType(), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence($exprResult->getVariableFlow(), VariableFlow::all(VariableFlow::READ_ALL), VariableFlow::throwing($throwPoint->getType(), \true)), $exprResult->getDependencies());
    }
}
