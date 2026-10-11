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
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @implements ExprHandler<AlwaysRememberedExpr>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/AlwaysRememberedExprHandler.cpp')]
final class AlwaysRememberedExprHandler implements ExprHandler
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
        return $expr instanceof AlwaysRememberedExpr;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $innerExpr = $expr->expr;
        $innerResult = $nodeScopeResolver->processExprNode($stmt, $innerExpr, $scope, $storage, $nodeCallback, $context);
        $scope = $innerResult->getScope();
        return $this->expressionResultFactory->create(
            $scope,
            $beforeScope,
            $expr,
            $innerResult->hasYield(),
            $innerResult->isAlwaysTerminating(),
            $innerResult->getThrowPoints(),
            $innerResult->getImpurePoints(),
            static fn(bool $nativeTypesPromoted): Type => $nativeTypesPromoted ? $expr->nativeType : $expr->type,
            // Narrowing by the remembered wrapper is narrowing by the inner
            // expression (TypeSpecifier unwrapped it and specified both keys);
            // the wrapper node itself keeps the default truthy/falsey entry.
            fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context)->unionWith($innerResult->getSpecifiedTypes($context, $nativeTypesPromoted)),
            \false,
            \null,
            \null,
            \null,
            // A type constraint on the remembered wrapper constrains both the wrapper
            // node (under its __phpstanRemembered(...) key) and the inner expression -
            // what TypeSpecifier::create() recovered by fanning the AlwaysRememberedExpr
            // out into wrapper + inner. The inner composes through its own child result;
            // raw-Expr callers still go through create()->createForExpr.
            function (Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($expr, $innerExpr, $innerResult, $beforeScope): SpecifiedTypes {
                $s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
                return $this->defaultNarrowingHelper->createSubjectTypes($s, $expr, null, $type, $context)->unionWith($this->defaultNarrowingHelper->createSubjectTypes($s, $innerExpr, $innerResult, $type, $context));
            },
            \null,
            \null,
            \null,
            $innerResult->getVariableFlow(),
            $innerResult->getDependencies()
        );
    }
}
