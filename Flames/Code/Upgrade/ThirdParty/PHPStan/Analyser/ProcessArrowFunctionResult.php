<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/ProcessArrowFunctionResult.cpp')]
final class ProcessArrowFunctionResult
{
    private \PHPStan\Analyser\ExpressionResult $expressionResult;
    private \PHPStan\Analyser\MutatingScope $arrowFunctionScope;
    /**
     * @var ThrowPoint[]
     */
    private array $closureTypeThrowPoints;
    /**
     * @var ImpurePoint[]
     */
    private array $closureTypeImpurePoints;
    /**
     * @var InvalidateExprNode[]
     */
    private array $invalidateExpressions;
    /**
     * @param ThrowPoint[] $closureTypeThrowPoints public throw points for building the arrow function type
     * @param ImpurePoint[] $closureTypeImpurePoints already merged in getClosureType() order (property-assign impure points first, then expression result impure points)
     * @param InvalidateExprNode[] $invalidateExpressions
     */
    public function __construct(\PHPStan\Analyser\ExpressionResult $expressionResult, \PHPStan\Analyser\MutatingScope $arrowFunctionScope, array $closureTypeThrowPoints, array $closureTypeImpurePoints, array $invalidateExpressions)
    {
        $this->expressionResult = $expressionResult;
        $this->arrowFunctionScope = $arrowFunctionScope;
        $this->closureTypeThrowPoints = $closureTypeThrowPoints;
        $this->closureTypeImpurePoints = $closureTypeImpurePoints;
        $this->invalidateExpressions = $invalidateExpressions;
    }
    public function getExpressionResult(): \PHPStan\Analyser\ExpressionResult
    {
        return $this->expressionResult;
    }
    public function getArrowFunctionScope(): \PHPStan\Analyser\MutatingScope
    {
        return $this->arrowFunctionScope;
    }
    /**
     * @return ThrowPoint[]
     */
    public function getClosureTypeThrowPoints(): array
    {
        return $this->closureTypeThrowPoints;
    }
    /**
     * @return ImpurePoint[]
     */
    public function getClosureTypeImpurePoints(): array
    {
        return $this->closureTypeImpurePoints;
    }
    /**
     * @return InvalidateExprNode[]
     */
    public function getInvalidateExpressions(): array
    {
        return $this->invalidateExpressions;
    }
}
