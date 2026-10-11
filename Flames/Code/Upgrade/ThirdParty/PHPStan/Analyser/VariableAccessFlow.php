<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/VariableAccessFlow.cpp')]
final class VariableAccessFlow extends \PHPStan\Analyser\VariableFlow
{
    /**
     * @readonly
     */
    public string $name;
    /**
     * @readonly
     */
    public ?VariableWrite $write;
    /**
     * @readonly
     */
    public ?Type $type;
    /**
     * @readonly
     */
    public ?int $targetId;
    /**
     * @readonly
     */
    public bool $container;
    /**
     * @readonly
     * @var int|string|null
     */
    public $offset;
    /**
     * @param VariableFlow::READ|VariableFlow::WRITE|VariableFlow::ESCAPE|VariableFlow::MENTION|VariableFlow::DEFINE|VariableFlow::DISCARD $kind
     * @param int|string|null $offset
     */
    public function __construct(string $kind, string $name, ?VariableWrite $write = null, ?Type $type = null, ?int $targetId = null, bool $container = \false, $offset = null)
    {
        $this->name = $name;
        $this->write = $write;
        $this->type = $type;
        $this->targetId = $targetId;
        $this->container = $container;
        $this->offset = $offset;
        parent::__construct($kind);
    }
}
