<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/InternalThrowPoint.cpp')]
final class InternalThrowPoint
{
    private \PHPStan\Analyser\MutatingScope $scope;
    private Type $type;
    /**
     * @var Node\Expr|Node\Stmt
     */
    private Node $node;
    private bool $explicit;
    private bool $canContainAnyThrowable;
    /**
     * @param Node\Expr|Node\Stmt $node
     */
    private function __construct(\PHPStan\Analyser\MutatingScope $scope, Type $type, Node $node, bool $explicit, bool $canContainAnyThrowable)
    {
        $this->scope = $scope;
        $this->type = $type;
        $this->node = $node;
        $this->explicit = $explicit;
        $this->canContainAnyThrowable = $canContainAnyThrowable;
    }
    public function toPublic(): \PHPStan\Analyser\ThrowPoint
    {
        if ($this->explicit) {
            return \PHPStan\Analyser\ThrowPoint::createExplicit($this->scope, $this->type, $this->node, $this->canContainAnyThrowable);
        }
        return \PHPStan\Analyser\ThrowPoint::createImplicit($this->scope, $this->node, $this->type);
    }
    /**
     * @param Node\Expr|Node\Stmt $node
     */
    public static function createExplicit(\PHPStan\Analyser\MutatingScope $scope, Type $type, Node $node, bool $canContainAnyThrowable): self
    {
        return new self($scope, $type, $node, \true, $canContainAnyThrowable);
    }
    /**
     * @param Node\Expr|Node\Stmt $node
     */
    public static function createImplicit(\PHPStan\Analyser\MutatingScope $scope, Node $node, ?Type $type = null): self
    {
        return new self($scope, $type ?? new ObjectType(Throwable::class), $node, \false, \true);
    }
    public static function createFromPublic(\PHPStan\Analyser\ThrowPoint $throwPoint, \PHPStan\Analyser\MutatingScope $scope): self
    {
        return new self($scope, $throwPoint->getType(), $throwPoint->getNode(), $throwPoint->isExplicit(), $throwPoint->canContainAnyThrowable());
    }
    public function getScope(): \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    /**
     * @return Node\Expr|Node\Stmt
     */
    public function getNode()
    {
        return $this->node;
    }
    public function isExplicit(): bool
    {
        return $this->explicit;
    }
    public function canContainAnyThrowable(): bool
    {
        return $this->canContainAnyThrowable;
    }
    public function subtractCatchType(Type $catchType): self
    {
        return new self($this->scope, TypeCombinator::remove($this->type, $catchType), $this->node, $this->explicit, $this->canContainAnyThrowable);
    }
}
