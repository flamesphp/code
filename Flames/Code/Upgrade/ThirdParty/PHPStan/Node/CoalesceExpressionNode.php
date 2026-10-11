<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Emitted by CoalesceHandler (for ??) and AssignOpHandler (for ??=) once the left
 * side is processed, so NullCoalesceRule reads the already-computed
 * IssetabilityResolution (via the carried ExpressionResult) instead of re-walking
 * the chain on demand. The operator description distinguishes ?? from ??=.
 *
 * @internal
 */
#[ReferencedByTurboExtension('coalesceExpressionNode')]
final class CoalesceExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $originalExpr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ExpressionResult $subjectResult;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ExpressionResult $rightResult;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $operatorDescription;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $originalExpr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ExpressionResult $subjectResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ExpressionResult $rightResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $operatorDescription
    )
    {
        $this->originalExpr = $originalExpr;
        $this->subjectResult = $subjectResult;
        $this->rightResult = $rightResult;
        $this->operatorDescription = $operatorDescription;
        parent::__construct($originalExpr->getAttributes());
    }
    public function getOriginalExpr(): Expr
    {
        return $this->originalExpr;
    }
    public function getSubjectResult(): ExpressionResult
    {
        return $this->subjectResult;
    }
    /** Result of the right side - the operand that's only evaluated when the left side is null. */
    public function getRightResult(): ExpressionResult
    {
        return $this->rightResult;
    }
    public function getOperatorDescription(): string
    {
        return $this->operatorDescription;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_CoalesceExpressionNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
