<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('breaklessWhileLoopNode')]
final class BreaklessWhileLoopNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public While_ $originalNode;
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
        While_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $exitPoints,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $hasYield
    )
    {
        $this->originalNode = $originalNode;
        $this->exitPoints = $exitPoints;
        $this->hasYield = $hasYield;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): While_
    {
        return $this->originalNode;
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
        return 'PHPStan_Node_BreaklessWhileLoop';
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
