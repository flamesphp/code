<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableControlFlow.cpp')]
final class VariableControlFlow extends \PHPStan\Analyser\VariableFlow
{
    /**
     * @readonly
     * @var list<VariableFlow|null>
     */
    public array $children;
    /**
     * @readonly
     */
    public ?string $name;
    /**
     * @readonly
     */
    public ?Type $type;
    /**
     * @readonly
     */
    public int $level;
    /**
     * @readonly
     */
    public bool $atLeastOnce;
    /**
     * @readonly
     */
    public bool $canExit;
    /**
     * @readonly
     * @var list<array{Type, VariableFlow|null}>
     */
    public array $catches;
    /**
     * @readonly
     */
    public ?ArrowFunction $arrow;
    /**
     * @readonly
     * @var list<array{VariableFlow|null, VariableFlow|null, bool}>
     */
    public array $cases;
    /**
     * @readonly
     */
    public bool $canRepeat;
    /**
     * @readonly
     */
    public bool $canContainAnyThrowable;
    /**
     * @readonly
     * @var \PhpParser\Node\Stmt\Foreach_|\PhpParser\Node\Stmt\For_|null
     */
    public $stmt;
    /**
     * @readonly
     * @var list<VariableWrite>
     */
    public array $bindings;
    /**
     * @readonly
     * @var list<VariableWrite>
     */
    public array $ownWrites;
    /**
     * @param VariableFlow::* $kind
     * @param list<VariableFlow|null> $children
     * @param list<array{Type, VariableFlow|null}> $catches
     * @param list<array{VariableFlow|null, VariableFlow|null, bool}> $cases
     * @param list<VariableWrite> $bindings
     * @param list<VariableWrite> $ownWrites
     * @param \PhpParser\Node\Stmt\Foreach_|\PhpParser\Node\Stmt\For_|null $stmt
     */
    public function __construct(string $kind, array $children = [], ?string $name = null, ?Type $type = null, int $level = 1, bool $atLeastOnce = \false, bool $canExit = \true, array $catches = [], ?ArrowFunction $arrow = null, array $cases = [], bool $canRepeat = \true, bool $canContainAnyThrowable = \false, $stmt = null, array $bindings = [], array $ownWrites = [])
    {
        $this->children = $children;
        $this->name = $name;
        $this->type = $type;
        $this->level = $level;
        $this->atLeastOnce = $atLeastOnce;
        $this->canExit = $canExit;
        $this->catches = $catches;
        $this->arrow = $arrow;
        $this->cases = $cases;
        $this->canRepeat = $canRepeat;
        $this->canContainAnyThrowable = $canContainAnyThrowable;
        $this->stmt = $stmt;
        $this->bindings = $bindings;
        $this->ownWrites = $ownWrites;
        parent::__construct($kind);
    }
}
