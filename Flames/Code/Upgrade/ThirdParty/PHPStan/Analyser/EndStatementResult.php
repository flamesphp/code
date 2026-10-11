<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Turbo\ShadowedByTurboExtension;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/EndStatementResult.cpp')]
final class EndStatementResult
{
    private Stmt $statement;
    private \PHPStan\Analyser\StatementResult $result;
    public function __construct(Stmt $statement, \PHPStan\Analyser\StatementResult $result)
    {
        $this->statement = $statement;
        $this->result = $result;
    }
    public function getStatement(): Stmt
    {
        return $this->statement;
    }
    public function getResult(): \PHPStan\Analyser\StatementResult
    {
        return $this->result;
    }
}
