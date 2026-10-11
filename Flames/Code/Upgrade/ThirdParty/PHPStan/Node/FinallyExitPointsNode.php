<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('finallyExitPointsNode')]
final class FinallyExitPointsNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var StatementExitPoint[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $finallyExitPoints;
    /**
     * @var StatementExitPoint[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $tryCatchExitPoints;
    /**
     * @param StatementExitPoint[] $finallyExitPoints
     * @param StatementExitPoint[] $tryCatchExitPoints
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $finallyExitPoints,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $tryCatchExitPoints
    )
    {
        $this->finallyExitPoints = $finallyExitPoints;
        $this->tryCatchExitPoints = $tryCatchExitPoints;
        parent::__construct([]);
    }
    /**
     * @return StatementExitPoint[]
     */
    public function getFinallyExitPoints(): array
    {
        return $this->finallyExitPoints;
    }
    /**
     * @return StatementExitPoint[]
     */
    public function getTryCatchExitPoints(): array
    {
        return $this->tryCatchExitPoints;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_FinallyExitPointsNode';
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
