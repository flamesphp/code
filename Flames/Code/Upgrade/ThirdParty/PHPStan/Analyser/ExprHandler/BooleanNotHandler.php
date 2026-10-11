<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<BooleanNot>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/BooleanNotHandler.cpp')]
final class BooleanNotHandler implements ExprHandler
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
        return $expr instanceof BooleanNot;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
        $scope = $exprResult->getScope();
        return $this->expressionResultFactory->create($scope, $beforeScope, $expr, $exprResult->hasYield(), $exprResult->isAlwaysTerminating(), $exprResult->getThrowPoints(), $exprResult->getImpurePoints(), static function (bool $nativeTypesPromoted) use ($exprResult): Type {
            $exprBooleanType = ($nativeTypesPromoted ? $exprResult->getNativeType() : $exprResult->getType())->toBoolean();
            if ($exprBooleanType->isTrue()->yes()) {
                return new ConstantBooleanType(\false);
            }
            if ($exprBooleanType->isFalse()->yes()) {
                return new ConstantBooleanType(\true);
            }
            return new BooleanType();
        }, function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $exprResult): SpecifiedTypes {
            if ($context->null()) {
                return $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
            }
            // The negated operand was processed above; compose its narrowing
            // directly from its result rather than re-resolving the node.
            return $exprResult->getSpecifiedTypes($context->negate(), $nativeTypesPromoted)->setRootExpr($expr);
        }, \false, \null, \null, \null, \null, \null, \null, \null, $exprResult->getVariableFlow(), $exprResult->getDependencies());
    }
}
