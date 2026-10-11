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
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\IssetExpr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * IssetExpr is a certainty marker IssetHandler wraps around the isset-tested
 * expression so a type specification can reduce that expression's existence
 * certainty (to maybe / unset) instead of narrowing its type. The specifications
 * carrying it read only its certainty, never its type - so the marker just
 * reports its inner expression's type, which lets it be priced like any other
 * node rather than being a special case in the resolution paths.
 *
 * @implements ExprHandler<IssetExpr>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/IssetExprHandler.cpp')]
final class IssetExprHandler implements ExprHandler
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
        return $expr instanceof IssetExpr;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        // because this is a virtual node handler, the caller will only be interested
        // in the type - we don't process the inner expr, just report its type
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], static fn(bool $nativeTypesPromoted): Type => $nodeScopeResolver->readScopeStateOrSyntheticType($expr->expr, $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context));
    }
}
