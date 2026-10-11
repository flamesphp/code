<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @template T of Stmt
 */
#[ExtensionInterface('phpstan.stmtHandler')]
#[ReferencedByTurboExtension('stmtHandler')]
interface StmtHandler
{
    /** @phpstan-assert-if-true T $stmt */
    public function supports(Stmt $stmt): bool;
    /**
     * @param T $stmt
     * @param callable(Node $node, Scope $scope): void $nodeCallback
     */
    public function processStmt(\PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, Stmt $stmt, \PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\ExpressionResultStorage $storage, callable $nodeCallback, \PHPStan\Analyser\StatementContext $context): \PHPStan\Analyser\InternalStatementResult;
}
