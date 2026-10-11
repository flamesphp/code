<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @template T of Expr
 */
#[ExtensionInterface('phpstan.exprHandler')]
#[ReferencedByTurboExtension('exprHandler')]
interface ExprHandler
{
    /** @phpstan-assert-if-true T $expr */
    public function supports(Expr $expr): bool;
    /**
     * @param T $expr
     * @param callable(Node $node, Scope $scope): void $nodeCallback
     */
    public function processExpr(\PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, \PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\ExpressionResultStorage $storage, callable $nodeCallback, \PHPStan\Analyser\ExpressionContext $context): \PHPStan\Analyser\ExpressionResult;
}
