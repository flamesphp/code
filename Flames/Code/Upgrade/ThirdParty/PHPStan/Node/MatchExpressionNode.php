<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('matchExpressionNode')]
final class MatchExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $condition;
    /**
     * @var MatchExpressionArm[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $arms;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $endScope;
    /**
     * @param MatchExpressionArm[] $arms
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $condition,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $arms,
        Expr\Match_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $endScope
    )
    {
        $this->condition = $condition;
        $this->arms = $arms;
        $this->endScope = $endScope;
        parent::__construct($originalNode->getAttributes());
    }
    public function getCondition(): Expr
    {
        return $this->condition;
    }
    /**
     * @return MatchExpressionArm[]
     */
    public function getArms(): array
    {
        return $this->arms;
    }
    public function getEndScope(): Scope
    {
        return $this->endScope;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_MatchExpression';
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
