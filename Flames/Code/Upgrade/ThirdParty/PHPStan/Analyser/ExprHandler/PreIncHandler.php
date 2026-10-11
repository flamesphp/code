<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IncDecTypeHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Analyser\VariableFlowBuilder;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * @implements ExprHandler<PreInc>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/PreIncHandler.cpp')]
final class PreIncHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    private IncDecTypeHelper $incDecTypeHelper;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    private \PHPStan\Analyser\ExprHandler\AssignHandler $assignHandler;
    public function __construct(ExpressionResultFactory $expressionResultFactory, IncDecTypeHelper $incDecTypeHelper, DefaultNarrowingHelper $defaultNarrowingHelper, \PHPStan\Analyser\ExprHandler\AssignHandler $assignHandler)
    {
        $this->expressionResultFactory = $expressionResultFactory;
        $this->incDecTypeHelper = $incDecTypeHelper;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
        $this->assignHandler = $assignHandler;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof PreInc;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $valueFlowWrite = VariableFlowBuilder::writeSite($expr->var, VariableWrite::KIND_PRE_INC, $scope, $storage);
        $valueContext = $valueFlowWrite !== null ? $context->enterDeep()->enterValueFlow($valueFlowWrite, \false) : $context->enterDeep();
        $varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $valueContext);
        $typeCallback = $this->incDecTypeHelper->getTypeCallback($expr->var, $varResult, \true);
        $specifyTypesCallback = fn(TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context);
        // the result standing for the whole inc/dec expression - threaded into
        // processVirtualAssign() as the value to assign so applyWrite() reads it
        // directly instead of re-processing the node on demand (which would
        // recurse)
        $incDecValueResult = $this->expressionResultFactory->create($varResult->getScope(), $scope, $expr, $varResult->hasYield(), $varResult->isAlwaysTerminating(), $varResult->getThrowPoints(), $varResult->getImpurePoints(), $typeCallback, $specifyTypesCallback);
        // processVirtualAssign() emits nodes (PropertyAssignNode) whose rules ask
        // about this whole expression - store its result first so those asks
        // answer from the storage; processExprNode() overwrites it with the
        // final result after this handler returns
        $nodeScopeResolver->storeExpressionResult($storage, $expr, $incDecValueResult);
        $assignedScope = $this->assignHandler->processVirtualAssign($nodeScopeResolver, $varResult->getScope(), $storage, $stmt, $expr->var, $expr, $nodeCallback, $incDecValueResult)->getScope();
        return $this->expressionResultFactory->create($assignedScope, $scope, $expr, $varResult->hasYield(), $varResult->isAlwaysTerminating(), $varResult->getThrowPoints(), $varResult->getImpurePoints(), $typeCallback, $specifyTypesCallback, \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence($varResult->getVariableFlow(), VariableFlowBuilder::targetWrite($expr->var, VariableWrite::KIND_PRE_INC, $assignedScope, $storage), $valueFlowWrite !== null && $context->isValueConsumed() ? VariableFlowBuilder::targetRead($expr->var, $storage, \true, $context->getValueFlowTarget() !== null ? $context->getValueFlowTarget()->id : null) : null), $varResult->getDependencies());
    }
}
