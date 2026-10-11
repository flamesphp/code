<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Turbo\ShadowedByTurboExtension;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableSequenceFlow.cpp')]
final class VariableSequenceFlow extends \PHPStan\Analyser\VariableFlow
{
    /**
     * @readonly
     * @var list<VariableFlow|null>
     */
    public array $children;
    /**
     * @param VariableFlow::SEQUENCE|VariableFlow::CHOICE $kind
     * @param list<VariableFlow|null> $children
     */
    public function __construct(string $kind, array $children)
    {
        $this->children = $children;
        parent::__construct($kind);
    }
}
