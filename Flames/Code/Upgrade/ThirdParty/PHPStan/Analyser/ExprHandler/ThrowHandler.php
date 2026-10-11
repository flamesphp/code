<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use function array_merge;
/**
 * @implements ExprHandler<Throw_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/ThrowHandler.cpp')]
final class ThrowHandler implements ExprHandler
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
        return $expr instanceof Throw_;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments())->enterThrow());
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \true, array_merge($exprResult->getThrowPoints(), [InternalThrowPoint::createExplicit($scope, $exprResult->getType(), $expr, \false)]), $exprResult->getImpurePoints(), static fn(bool $nativeTypesPromoted): Type => new NonAcceptingNeverType(), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence($exprResult->getVariableFlow(), VariableFlow::throwing($exprResult->getType(), \false)), $exprResult->getDependencies());
    }
}
