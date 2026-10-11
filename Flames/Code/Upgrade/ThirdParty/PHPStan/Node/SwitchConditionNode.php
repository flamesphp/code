<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Virtual node emitted once per `switch` statement. It pairs the switch subject
 * with each non-default `case` condition so rules can inspect the loose `==`
 * comparison the `switch` performs, using the scope captured at each case
 * (which already excludes the values matched by earlier cases).
 *
 * @api
 */
#[ReferencedByTurboExtension('switchConditionNode')]
final class SwitchConditionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $subject;
    /**
     * @var SwitchConditionArm[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $arms;
    /**
     * @param SwitchConditionArm[] $arms
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $subject,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $arms,
        Switch_ $originalNode
    )
    {
        $this->subject = $subject;
        $this->arms = $arms;
        parent::__construct($originalNode->getAttributes());
    }
    public function getSubject(): Expr
    {
        return $this->subject;
    }
    /**
     * @return SwitchConditionArm[]
     */
    public function getArms(): array
    {
        return $this->arms;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_SwitchCondition';
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
