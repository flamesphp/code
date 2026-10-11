<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Dependency\Dependencies;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ProcessClosureResult.cpp')]
final class ProcessClosureResult
{
    private \PHPStan\Analyser\MutatingScope $scope;
    /**
     * @var InternalThrowPoint[]
     */
    private array $throwPoints;
    /**
     * @var ImpurePoint[]
     */
    private array $impurePoints;
    /**
     * @var InvalidateExprNode[]
     */
    private array $invalidateExpressions;
    /**
     * @var list<array{Return_, Scope}>
     */
    private array $gatheredReturnStatements;
    /**
     * @var list<array{Yield_|YieldFrom, Scope}>
     */
    private array $gatheredYieldStatements;
    /**
     * @var list<ExecutionEndNode>
     */
    private array $executionEnds;
    /**
     * @var ImpurePoint[]
     */
    private array $closureTypeImpurePoints;
    private ?\PHPStan\Analyser\MutatingScope $byRefClosureResultScope;
    /**
     * @var ClosureUse[]
     */
    private array $byRefUses;
    private ?Dependencies $dependencies;
    /**
     * @param InternalThrowPoint[] $throwPoints
     * @param ImpurePoint[] $impurePoints
     * @param InvalidateExprNode[] $invalidateExpressions
     * @param list<array{Return_, Scope}> $gatheredReturnStatements
     * @param list<array{Yield_|YieldFrom, Scope}> $gatheredYieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $closureTypeImpurePoints already merged in getClosureType() order (property-assign impure points first, then statement result impure points)
     * @param ClosureUse[] $byRefUses
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, array $throwPoints, array $impurePoints, array $invalidateExpressions, array $gatheredReturnStatements, array $gatheredYieldStatements, array $executionEnds, array $closureTypeImpurePoints, ?\PHPStan\Analyser\MutatingScope $byRefClosureResultScope = null, array $byRefUses = [], ?Dependencies $dependencies = null)
    {
        $this->scope = $scope;
        $this->throwPoints = $throwPoints;
        $this->impurePoints = $impurePoints;
        $this->invalidateExpressions = $invalidateExpressions;
        $this->gatheredReturnStatements = $gatheredReturnStatements;
        $this->gatheredYieldStatements = $gatheredYieldStatements;
        $this->executionEnds = $executionEnds;
        $this->closureTypeImpurePoints = $closureTypeImpurePoints;
        $this->byRefClosureResultScope = $byRefClosureResultScope;
        $this->byRefUses = $byRefUses;
        $this->dependencies = $dependencies;
    }
    /**
     * What the parameters and the body of the closure depend on.
     */
    public function getDependencies(): ?Dependencies
    {
        return $this->dependencies;
    }
    public function getScope(): \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    public function applyByRefUseScope(\PHPStan\Analyser\MutatingScope $scope): \PHPStan\Analyser\MutatingScope
    {
        if ($this->byRefClosureResultScope === null) {
            return $scope;
        }
        return $scope->processClosureScope($this->byRefClosureResultScope, null, $this->byRefUses);
    }
    /**
     * @return InternalThrowPoint[]
     */
    public function getThrowPoints(): array
    {
        return $this->throwPoints;
    }
    /**
     * @return ImpurePoint[]
     */
    public function getImpurePoints(): array
    {
        return $this->impurePoints;
    }
    /**
     * @return InvalidateExprNode[]
     */
    public function getInvalidateExpressions(): array
    {
        return $this->invalidateExpressions;
    }
    /**
     * @return list<array{Return_, Scope}>
     */
    public function getGatheredReturnStatements(): array
    {
        return $this->gatheredReturnStatements;
    }
    /**
     * @return list<array{Yield_|YieldFrom, Scope}>
     */
    public function getGatheredYieldStatements(): array
    {
        return $this->gatheredYieldStatements;
    }
    /**
     * @return list<ExecutionEndNode>
     */
    public function getExecutionEnds(): array
    {
        return $this->executionEnds;
    }
    /**
     * The closure body's impure points already merged in getClosureType() order
     * (property-assign impure points first, then statement result impure points),
     * ready to feed ClosureTypeResolver::buildClosureTypeForClosure().
     *
     * @return ImpurePoint[]
     */
    public function getClosureTypeImpurePoints(): array
    {
        return $this->closureTypeImpurePoints;
    }
}
