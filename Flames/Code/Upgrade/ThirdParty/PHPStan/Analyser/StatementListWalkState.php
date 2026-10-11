<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Dependency\Dependencies;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * The state NodeScopeResolver threads from one statement of a statement list
 * to the next. A snapshot (clone) taken before a statement is everything the
 * walk needs to continue from that statement again.
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/StatementListWalkState.cpp')]
final class StatementListWalkState
{
    public \PHPStan\Analyser\MutatingScope $scope;
    /** @var array<int, VariableFlow|null> */
    public array $variableFlows = [];
    public bool $alreadyTerminated = \false;
    public bool $hasYield = \false;
    /** @var InternalStatementExitPoint[] */
    public array $exitPoints = [];
    /** @var InternalThrowPoint[] */
    public array $throwPoints = [];
    /** @var ImpurePoint[] */
    public array $impurePoints = [];
    /** @var list<Dependencies> */
    public array $dependencies = [];
    public function __construct(\PHPStan\Analyser\MutatingScope $scope)
    {
        $this->scope = $scope;
    }
    public function toResult(): \PHPStan\Analyser\InternalStatementResult
    {
        return new \PHPStan\Analyser\InternalStatementResult($this->scope, $this->hasYield, $this->alreadyTerminated, $this->exitPoints, $this->throwPoints, $this->impurePoints, [], \PHPStan\Analyser\VariableFlow::sequence(...$this->variableFlows), \null, Dependencies::merge(...$this->dependencies));
    }
}
