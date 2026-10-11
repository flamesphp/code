<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableInputFlow.cpp')]
final class VariableInputFlow extends \PHPStan\Analyser\VariableFlow
{
    /**
     * @readonly
     */
    public int $writeId;
    /**
     * @readonly
     */
    public ?int $targetId;
    public function __construct(int $writeId, ?int $targetId)
    {
        $this->writeId = $writeId;
        $this->targetId = $targetId;
        parent::__construct(self::SEQUENCE);
    }
}
