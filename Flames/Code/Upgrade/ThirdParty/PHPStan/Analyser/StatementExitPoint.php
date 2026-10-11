<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/StatementExitPoint.cpp')]
final class StatementExitPoint
{
    private Stmt $statement;
    private \PHPStan\Analyser\Scope $scope;
    public function __construct(Stmt $statement, \PHPStan\Analyser\Scope $scope)
    {
        $this->statement = $statement;
        $this->scope = $scope;
    }
    public function getStatement(): Stmt
    {
        return $this->statement;
    }
    public function getScope(): \PHPStan\Analyser\Scope
    {
        return $this->scope;
    }
}
