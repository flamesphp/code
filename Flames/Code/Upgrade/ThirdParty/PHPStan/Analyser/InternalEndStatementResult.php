<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/InternalEndStatementResult.cpp')]
final class InternalEndStatementResult
{
    private Stmt $statement;
    private \PHPStan\Analyser\InternalStatementResult $result;
    public function __construct(Stmt $statement, \PHPStan\Analyser\InternalStatementResult $result)
    {
        $this->statement = $statement;
        $this->result = $result;
    }
    public function toPublic(): \PHPStan\Analyser\EndStatementResult
    {
        return new \PHPStan\Analyser\EndStatementResult($this->statement, $this->result->toPublic());
    }
    public function getStatement(): Stmt
    {
        return $this->statement;
    }
    public function getResult(): \PHPStan\Analyser\InternalStatementResult
    {
        return $this->result;
    }
}
