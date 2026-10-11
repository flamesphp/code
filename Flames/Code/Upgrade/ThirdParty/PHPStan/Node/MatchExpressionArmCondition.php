<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('matchExpressionArmCondition')]
final class MatchExpressionArmCondition
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $condition;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $line;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $condition,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $line
    )
    {
        $this->condition = $condition;
        $this->scope = $scope;
        $this->line = $line;
    }
    public function getCondition(): Expr
    {
        return $this->condition;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
    public function getLine(): int
    {
        return $this->line;
    }
}
