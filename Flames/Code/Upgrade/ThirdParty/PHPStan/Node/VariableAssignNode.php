<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('variableAssignNode')]
final class VariableAssignNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr\Variable $variable;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $assignedExpr;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr\Variable $variable,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $assignedExpr
    )
    {
        $this->variable = $variable;
        $this->assignedExpr = $assignedExpr;
        parent::__construct($variable->getAttributes());
    }
    public function getVariable(): Expr\Variable
    {
        return $this->variable;
    }
    public function getAssignedExpr(): Expr
    {
        return $this->assignedExpr;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_VariableAssignNodeNode';
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
