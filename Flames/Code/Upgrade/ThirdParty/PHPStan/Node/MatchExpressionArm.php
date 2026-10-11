<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('matchExpressionArm')]
final class MatchExpressionArm
{
    private \PHPStan\Node\MatchExpressionArmBody $body;
    /**
     * @var MatchExpressionArmCondition[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $conditions;
    private int $line;
    /**
     * @param MatchExpressionArmCondition[] $conditions
     */
    public function __construct(
        \PHPStan\Node\MatchExpressionArmBody $body,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $conditions,
        int $line
    )
    {
        $this->body = $body;
        $this->conditions = $conditions;
        $this->line = $line;
    }
    public function getBody(): \PHPStan\Node\MatchExpressionArmBody
    {
        return $this->body;
    }
    /**
     * @return MatchExpressionArmCondition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
    public function getLine(): int
    {
        return $this->line;
    }
}
