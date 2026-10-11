<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;
use function is_string;
use function spl_object_id;
/** Compose variable flow for assignment targets and arguments. */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableFlowBuilder.cpp')]
final class VariableFlowBuilder
{
    /** @param InternalThrowPoint[] $throwPoints */
    public static function throws(Expr $expr, array $throwPoints): ?\PHPStan\Analyser\VariableFlow
    {
        // a callback the callee invokes immediately throws through the call -
        // its throw points are re-created on the callback argument node
        $callbackArguments = [];
        if ($expr instanceof Expr\CallLike && !$expr->isFirstClassCallable()) {
            foreach ($expr->getArgs() as $arg) {
                if (!$arg->value instanceof Expr\Closure && !$arg->value instanceof Expr\ArrowFunction) {
                    continue;
                }
                $callbackArguments[] = $arg->value;
            }
        }
        $throws = [];
        foreach ($throwPoints as $throw) {
            if ($throw->getNode() !== $expr && !in_array($throw->getNode(), $callbackArguments, \true) && ($throw->getNode()->getStartFilePos() !== $expr->getStartFilePos() || $throw->getNode()->getEndFilePos() !== $expr->getEndFilePos())) {
                continue;
            }
            $throws[] = \PHPStan\Analyser\VariableFlow::throwing($throw->getType(), \true, $throw->canContainAnyThrowable());
        }
        return \PHPStan\Analyser\VariableFlow::sequence(...$throws);
    }
    public static function arguments(Expr\CallLike $call, \PHPStan\Analyser\ArgsResult $argsResult, \PHPStan\Analyser\ExpressionResultStorage $storage): ?\PHPStan\Analyser\VariableFlow
    {
        $flows = [];
        foreach ($call->getArgs() as $arg) {
            $result = $argsResult->findArgResult($arg->value) ?? $storage->findExpressionResult($arg->value);
            $flows[] = $result !== null ? $result->getVariableFlow() : null;
            if (!$arg->byRef && !$argsResult->isPassedByReference($arg->value)) {
                continue;
            }
            $flows[] = self::escapeRoot($arg->value);
        }
        return \PHPStan\Analyser\VariableFlow::sequence(...$flows);
    }
    public static function child(?Node $node, \PHPStan\Analyser\ExpressionResultStorage $storage): ?\PHPStan\Analyser\VariableFlow
    {
        if ($node instanceof Expr) {
            $result = $storage->findExpressionResult($node);
            return $result !== null ? $result->getVariableFlow() : null;
        }
        return null;
    }
    public static function targetRead(Expr $target, \PHPStan\Analyser\ExpressionResultStorage $storage, bool $read, ?int $targetId = null): ?\PHPStan\Analyser\VariableFlow
    {
        if ($target instanceof Expr\Variable) {
            return is_string($target->name) ? $read ? \PHPStan\Analyser\VariableFlow::read($target->name, $targetId) : null : self::child($target->name, $storage);
        }
        if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
            return null;
        }
        if ($target instanceof Expr\ArrayDimFetch) {
            if ($target->var instanceof Expr\Variable && is_string($target->var->name)) {
                $dimResult = $target->dim !== null ? $storage->findExpressionResult($target->dim) : null;
                return \PHPStan\Analyser\VariableFlow::sequence(\PHPStan\Analyser\VariableFlow::read($target->var->name, $targetId, !$read, $read && $dimResult !== null ? \PHPStan\Analyser\VariableWriteOffset::fromType($dimResult->getType()) : null), self::child($target->dim, $storage));
            }
            return \PHPStan\Analyser\VariableFlow::sequence(self::targetRead($target->var, $storage, \true, $targetId), self::child($target->dim, $storage));
        }
        if ($target instanceof Expr\PropertyFetch || $target instanceof Expr\NullsafePropertyFetch) {
            return \PHPStan\Analyser\VariableFlow::sequence(self::child($target->var, $storage), self::child($target->name, $storage));
        }
        if ($target instanceof Expr\StaticPropertyFetch) {
            return \PHPStan\Analyser\VariableFlow::sequence(self::child($target->class, $storage), self::child($target->name, $storage));
        }
        return self::child($target, $storage);
    }
    /**
     * The writes of a flow composed only of accesses and sequences - an
     * assignment target or a loop head.
     *
     * @return list<VariableWrite>
     */
    public static function writes(?\PHPStan\Analyser\VariableFlow $flow): array
    {
        if ($flow === null) {
            return [];
        }
        if ($flow instanceof \PHPStan\Analyser\VariableAccessFlow) {
            return $flow->write !== null ? [$flow->write] : [];
        }
        if (!$flow instanceof \PHPStan\Analyser\VariableSequenceFlow) {
            return [];
        }
        $writes = [];
        foreach ($flow->children as $child) {
            foreach (self::writes($child) as $write) {
                $writes[] = $write;
            }
        }
        return $writes;
    }
    /** @param VariableWrite::KIND_* $kind */
    public static function targetWrite(Expr $target, int $kind, \PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\ExpressionResultStorage $storage, ?Type $redundant = null): ?\PHPStan\Analyser\VariableFlow
    {
        if ($target instanceof Expr\List_ || $target instanceof Expr\Array_) {
            $writes = [];
            foreach ($target->items as $item) {
                if ($item === null) {
                    continue;
                }
                $writes[] = \PHPStan\Analyser\VariableFlow::sequence(self::child($item->key, $storage), self::targetRead($item->value, $storage, \false), self::targetWrite($item->value, VariableWrite::KIND_LIST_ITEM, $scope, $storage), $item->byRef ? self::escapeRoot($item->value) : null);
            }
            return \PHPStan\Analyser\VariableFlow::sequence(...$writes);
        }
        $write = self::writeSite($target, $kind, $scope, $storage);
        if ($write === null) {
            return null;
        }
        $flow = \PHPStan\Analyser\VariableFlow::write($write, $redundant);
        $name = $write->variableName;
        if (!$write->offsetWrite && !$scope->hasVariableType($name)->no() && self::holdsDestructibleObject($scope->getVariableType($name))) {
            // the destructor observes the value when the variable releases it -
            // overwritten, unset or going out of scope - so a variable holding
            // the object keeps it alive on purpose (a scope guard)
            return \PHPStan\Analyser\VariableFlow::sequence($flow, \PHPStan\Analyser\VariableFlow::escape($name));
        }
        return $flow;
    }
    private static function holdsDestructibleObject(Type $type): bool
    {
        if ($type->isObject()->no()) {
            return \false;
        }
        foreach (TypeCombinator::removeNull($type)->getObjectClassReflections() as $classReflection) {
            if ($classReflection->hasNativeMethod('__destruct')) {
                return \true;
            }
        }
        return \false;
    }
    /** @param VariableWrite::KIND_* $kind */
    public static function writeSite(Expr $target, int $kind, \PHPStan\Analyser\MutatingScope $scope, \PHPStan\Analyser\ExpressionResultStorage $storage): ?VariableWrite
    {
        if ($target instanceof Expr\Variable && is_string($target->name)) {
            if ($target->name === 'this' || in_array($target->name, \PHPStan\Analyser\Scope::SUPERGLOBAL_VARIABLES, \true)) {
                return null;
            }
            return new VariableWrite($target->name, $target, spl_object_id($target), $kind);
        }
        if (!$target instanceof Expr\ArrayDimFetch) {
            return null;
        }
        $first = $target;
        while ($first->var instanceof Expr\ArrayDimFetch) {
            $first = $first->var;
        }
        $root = $first->var;
        if (!$root instanceof Expr\Variable || !is_string($root->name) || $root->name === 'this' || in_array($root->name, \PHPStan\Analyser\Scope::SUPERGLOBAL_VARIABLES, \true)) {
            return null;
        }
        if (!$scope->hasVariableType($root->name)->no()) {
            $type = $scope->getVariableType($root->name);
            if (!$type->isArray()->yes() && !$type->isString()->yes()) {
                return null;
            }
        }
        $dimResult = $first->dim !== null ? $storage->findExpressionResult($first->dim) : null;
        $offset = $dimResult !== null ? \PHPStan\Analyser\VariableWriteOffset::fromType($dimResult->getType()) : null;
        return new VariableWrite($root->name, $target, spl_object_id($target), $kind, \true, $offset, \null, $first === $target);
    }
    public static function escapeRoot(Expr $expr): ?\PHPStan\Analyser\VariableFlow
    {
        while ($expr instanceof Expr\ArrayDimFetch) {
            $expr = $expr->var;
        }
        return $expr instanceof Expr\Variable && is_string($expr->name) ? \PHPStan\Analyser\VariableFlow::escape($expr->name) : null;
    }
}
