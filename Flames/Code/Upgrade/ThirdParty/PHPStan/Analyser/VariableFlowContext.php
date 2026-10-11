<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Type\Type;
/** Live variables at the surrounding control-flow destinations. */
final class VariableFlowContext
{
    /**
     * @readonly
     * @var array<string, true>
     */
    public array $return;
    /**
     * @readonly
     * @var list<array<string, true>>
     */
    public array $breaks;
    /**
     * @readonly
     * @var list<array<string, true>>
     */
    public array $continues;
    /**
     * @readonly
     * @var list<array{Type, array<string, true>}>
     */
    public array $catches;
    /**
     * @readonly
     * @var array<string, true>
     */
    public array $uncaught;
    /**
     * @param array<string, true> $return
     * @param list<array<string, true>> $breaks
     * @param list<array<string, true>> $continues
     * @param list<array{Type, array<string, true>}> $catches
     * @param array<string, true> $uncaught
     */
    public function __construct(array $return, array $breaks = [], array $continues = [], array $catches = [], array $uncaught = [])
    {
        $this->return = $return;
        $this->breaks = $breaks;
        $this->continues = $continues;
        $this->catches = $catches;
        $this->uncaught = $uncaught;
    }
}
