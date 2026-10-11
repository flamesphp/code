<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('doWhileLoopConditionNode')]
final class DoWhileLoopConditionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $cond;
    /**
     * @var StatementExitPoint[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $exitPoints;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $hasYield;
    /**
     * @param StatementExitPoint[] $exitPoints
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $cond,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $exitPoints,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $hasYield
    )
    {
        $this->cond = $cond;
        $this->exitPoints = $exitPoints;
        $this->hasYield = $hasYield;
        parent::__construct($cond->getAttributes());
    }
    public function getCond(): Expr
    {
        return $this->cond;
    }
    /**
     * @return StatementExitPoint[]
     */
    public function getExitPoints(): array
    {
        return $this->exitPoints;
    }
    public function hasYield(): bool
    {
        return $this->hasYield;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClosureReturnStatementsNode';
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
