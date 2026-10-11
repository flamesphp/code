<?php

declare (strict_types=1);
namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Block;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\InternalStatementResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\StmtHandler;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * @implements StmtHandler<Block>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/BlockHandler.cpp')]
final class BlockHandler implements StmtHandler
{
    private bool $polluteScopeWithBlock;
    public function __construct(
        #[AutowiredParameter]
        bool $polluteScopeWithBlock
    )
    {
        $this->polluteScopeWithBlock = $polluteScopeWithBlock;
    }
    public function supports(Stmt $stmt): bool
    {
        return $stmt instanceof Block;
    }
    public function processStmt(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, StatementContext $context): InternalStatementResult
    {
        $dependencies = [];
        $result = $nodeScopeResolver->processStmtNodesInternal($stmt, $stmt->stmts, $scope, $storage, $nodeCallback, $context);
        $dependencies[] = $result->getDependencies();
        // like a loop body, the variable flow keeps the block optional whatever
        // polluteScopeWithBlock says: a write inside it does not make an
        // earlier write of the variable dead
        $variableFlow = VariableFlow::choice($result->getVariableFlow(), null);
        if ($this->polluteScopeWithBlock) {
            return $result->withVariableFlow($variableFlow);
        }
        return new InternalStatementResult($scope->mergeWith($result->getScope()), $result->hasYield(), $result->isAlwaysTerminating(), $result->getExitPoints(), $result->getThrowPoints(), $result->getImpurePoints(), $result->getEndStatements(), $variableFlow, \null, Dependencies::merge(...$dependencies));
    }
}
