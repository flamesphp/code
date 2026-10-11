<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('intertwinedVariableByReferenceWithExpr')]
final class IntertwinedVariableByReferenceWithExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $variableName;
    public Expr $expr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $assignedExpr;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $variableName,
        Expr $expr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $assignedExpr
    )
    {
        $this->variableName = $variableName;
        $this->expr = $expr;
        $this->assignedExpr = $assignedExpr;
        parent::__construct([]);
    }
    public function getVariableName(): string
    {
        return $this->variableName;
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    public function getAssignedExpr(): Expr
    {
        return $this->assignedExpr;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_IntertwinedVariableByReferenceWithExpr';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return ['expr'];
    }
}
