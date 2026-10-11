<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;
/**
 * Immutable source execution fragment, composed with expression and statement
 * results. Liveness is resolved at the body boundary, independently of types.
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableFlow.cpp')]
abstract class VariableFlow
{
    /**
     * @readonly
     * @var self::*
     */
    public string $kind;
    public const SEQUENCE = 'sequence';
    public const CHOICE = 'choice';
    public const LOOP = 'loop';
    public const TRY_CATCH = 'try';
    public const SWITCH = 'switch';
    public const READ = 'read';
    public const WRITE = 'write';
    public const DEFINE = 'define';
    public const DISCARD = 'discard';
    public const ESCAPE = 'escape';
    public const MENTION = 'mention';
    public const READ_ALL = 'readAll';
    public const MENTION_ALL = 'mentionAll';
    public const OPAQUE = 'opaque';
    public const RETURN = 'return';
    public const BREAK = 'break';
    public const CONTINUE = 'continue';
    public const THROW = 'throw';
    public const STOP = 'stop';
    public const ARROW = 'arrow';
    public const LOOP_STATEMENT = 'loopStatement';
    /** @param self::* $kind */
    protected function __construct(string $kind)
    {
        $this->kind = $kind;
    }
    public static function sequence(?self ...$flows): ?self
    {
        $nonEmpty = [];
        foreach ($flows as $flow) {
            if ($flow === null) {
                continue;
            }
            $nonEmpty[] = $flow;
        }
        if (count($nonEmpty) === 0) {
            return null;
        }
        if (count($nonEmpty) === 1) {
            return $nonEmpty[0];
        }
        return new \PHPStan\Analyser\VariableSequenceFlow(self::SEQUENCE, $nonEmpty);
    }
    /** @no-named-arguments */
    public static function choice(?self ...$branches): ?self
    {
        if (count($branches) === 0) {
            return null;
        }
        if (count($branches) === 1 || count($branches) === 2 && $branches[0] === $branches[1]) {
            return $branches[0];
        }
        return new \PHPStan\Analyser\VariableSequenceFlow(self::CHOICE, $branches);
    }
    public static function arrow(ArrowFunction $arrow, ?self $body, ?self $outputs): self
    {
        return new \PHPStan\Analyser\VariableControlFlow(self::ARROW, [$body, $outputs], \null, \null, 1, \false, \true, [], $arrow);
    }
    /** @param int|string|null $offset */
    public static function read(string $name, ?int $targetId = null, bool $container = \false, $offset = null): ?self
    {
        if ($name === 'this' || in_array($name, \PHPStan\Analyser\Scope::SUPERGLOBAL_VARIABLES, \true)) {
            return null;
        }
        return new \PHPStan\Analyser\VariableAccessFlow(self::READ, $name, \null, \null, $targetId, $container, $offset);
    }
    public static function conditional(?self $condition, ?self $if, ?self $else): ?self
    {
        return self::sequence($condition, self::choice($if, $else));
    }
    /** @param list<array{self|null, self|null, bool}> $cases */
    public static function switch(?self $condition, array $cases, bool $exhaustive): self
    {
        return new \PHPStan\Analyser\VariableControlFlow(self::SWITCH, [$condition], \null, \null, 1, \false, !$exhaustive, [], \null, $cases);
    }
    public static function write(VariableWrite $write, ?Type $redundantType = null): self
    {
        return new \PHPStan\Analyser\VariableAccessFlow($write->parentId === null ? self::WRITE : self::DEFINE, $write->variableName, $write, $redundantType);
    }
    public static function discard(VariableWrite $write): self
    {
        return new \PHPStan\Analyser\VariableAccessFlow(self::DISCARD, $write->variableName, $write);
    }
    /** The enclosing expression consumes a write's inputs, without reading its target. */
    public static function inputs(int $writeId, ?int $targetId): self
    {
        return new \PHPStan\Analyser\VariableInputFlow($writeId, $targetId);
    }
    public static function escape(string $name): self
    {
        return new \PHPStan\Analyser\VariableAccessFlow(self::ESCAPE, $name);
    }
    public static function mention(string $name): self
    {
        return new \PHPStan\Analyser\VariableAccessFlow(self::MENTION, $name);
    }
    /** @param self::READ_ALL|self::MENTION_ALL|self::OPAQUE $kind */
    public static function all(string $kind): self
    {
        return new \PHPStan\Analyser\VariableControlFlow($kind);
    }
    /** @param self::RETURN|self::BREAK|self::CONTINUE|self::STOP $kind */
    public static function exit(string $kind, int $level = 1, ?string $name = null): self
    {
        return new \PHPStan\Analyser\VariableControlFlow($kind, [], $name, \null, $level);
    }
    public static function throwing(Type $type, bool $canContinue, bool $canContainAnyThrowable = \false): self
    {
        return new \PHPStan\Analyser\VariableControlFlow(self::THROW, [], \null, $type, 1, \false, $canContinue, [], \null, [], \true, $canContainAnyThrowable);
    }
    public static function loop(?self $condition, ?self $body, ?self $update, bool $atLeastOnce, bool $canExit, bool $canRepeat = \true): self
    {
        return new \PHPStan\Analyser\VariableControlFlow(self::LOOP, [$condition, $body, $update], \null, \null, 1, $atLeastOnce, $canExit, [], \null, [], $canRepeat);
    }
    /**
     * A loop statement whose head binds variables - a foreach key/value or
     * a for-loop initial assignment. The bindings are checked for reusing a
     * variable that is assigned before the statement and read after it;
     * the statement's own writes (the bindings and a for-loop update) are
     * the only assignments allowed in between.
     *
     * @param list<VariableWrite> $bindings
     * @param list<VariableWrite> $ownWrites
     * @param \PhpParser\Node\Stmt\Foreach_|\PhpParser\Node\Stmt\For_ $stmt
     */
    public static function loopStatement($stmt, ?self $flow, array $bindings, array $ownWrites): ?self
    {
        if ($bindings === []) {
            return $flow;
        }
        return new \PHPStan\Analyser\VariableControlFlow(self::LOOP_STATEMENT, [$flow], \null, \null, 1, \false, \true, [], \null, [], \true, \false, $stmt, $bindings, $ownWrites);
    }
    /** @param list<array{Type, self|null}> $catches */
    public static function tryCatch(?self $body, array $catches, ?self $finally): self
    {
        return new \PHPStan\Analyser\VariableControlFlow(self::TRY_CATCH, [$body, $finally], \null, \null, 1, \false, \true, $catches);
    }
}
