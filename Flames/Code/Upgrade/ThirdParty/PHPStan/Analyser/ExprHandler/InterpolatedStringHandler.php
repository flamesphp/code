<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_merge;
use function spl_object_id;
/**
 * @implements ExprHandler<InterpolatedString>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/InterpolatedStringHandler.cpp')]
final class InterpolatedStringHandler implements ExprHandler
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ImplicitToStringCallHelper $implicitToStringCallHelper;
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ImplicitToStringCallHelper $implicitToStringCallHelper, ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->implicitToStringCallHelper = $implicitToStringCallHelper;
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof InterpolatedString;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $hasYield = \false;
        $throwPoints = [];
        $variableFlows = [];
        $dependencies = [];
        $impurePoints = [];
        $isAlwaysTerminating = \false;
        /** @var array<int, ExpressionResult> $partResults */
        $partResults = [];
        foreach ($expr->parts as $part) {
            if (!$part instanceof Expr) {
                continue;
            }
            $partResult = $nodeScopeResolver->processExprNode($stmt, $part, $scope, $storage, $nodeCallback, $context->enterDeepKeepingValueFlow());
            $variableFlows[] = $partResult->getVariableFlow();
            $dependencies[] = $partResult->getDependencies();
            $partResults[spl_object_id($part)] = $partResult;
            $hasYield = $hasYield || $partResult->hasYield();
            $throwPoints = array_merge($throwPoints, $partResult->getThrowPoints());
            $impurePoints = array_merge($impurePoints, $partResult->getImpurePoints());
            $toStringResult = $this->implicitToStringCallHelper->processImplicitToStringCall($part, $scope, $partResult);
            $throwPoints = array_merge($throwPoints, $toStringResult->getThrowPoints());
            $impurePoints = array_merge($impurePoints, $toStringResult->getImpurePoints());
            $isAlwaysTerminating = $isAlwaysTerminating || $partResult->isAlwaysTerminating();
            $scope = $partResult->getScope();
        }
        return $this->expressionResultFactory->create($scope, $beforeScope, $expr, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints, function (bool $nativeTypesPromoted) use ($expr, $partResults): Type {
            $resultType = null;
            foreach ($expr->parts as $part) {
                if ($part instanceof InterpolatedStringPart) {
                    $partType = new ConstantStringType($part->value);
                } else {
                    $partResult = $partResults[spl_object_id($part)];
                    $partType = ($nativeTypesPromoted ? $partResult->getNativeType() : $partResult->getType())->toString();
                }
                if ($resultType === null) {
                    $resultType = $partType;
                    continue;
                }
                $resultType = $this->initializerExprTypeResolver->resolveConcatType($resultType, $partType);
            }
            return $resultType ?? new ConstantStringType('');
        }, fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence(...$variableFlows), Dependencies::merge(...$dependencies));
    }
}
