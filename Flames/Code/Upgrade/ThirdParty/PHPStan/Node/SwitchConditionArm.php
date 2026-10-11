<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * A single non-default `case` of a `switch`, paired with the scope captured
 * right after the case condition was processed (which already excludes the
 * values matched by earlier terminating cases).
 *
 * @api
 */
#[ReferencedByTurboExtension('switchConditionArm')]
final class SwitchConditionArm
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $caseCondition;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $line;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isLast;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $caseCondition,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $line,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isLast
    )
    {
        $this->caseCondition = $caseCondition;
        $this->scope = $scope;
        $this->line = $line;
        $this->isLast = $isLast;
    }
    public function getCaseCondition(): Expr
    {
        return $this->caseCondition;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
    public function getLine(): int
    {
        return $this->line;
    }
    /**
     * Whether this is the last non-default `case` of the `switch` (only a
     * `default` may follow it), in which case an always-true comparison is fine
     * because it does not make any subsequent `case` unreachable. A trailing
     * `default` is not considered a `case` it would make unreachable.
     */
    public function isLast(): bool
    {
        return $this->isLast;
    }
}
