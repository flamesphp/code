<?php

declare (strict_types=1);
namespace PhpParser\Node\Stmt;

use PhpParser\Node\StaticVar;
use PhpParser\Node\Stmt;
class Static_ extends Stmt
{
    /**
     * Constructs a static variables list node.
     *
     * @param StaticVar[] $vars Variable definitions
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $vars, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['vars'];
    }
    public function getType(): string
    {
        return 'Stmt_Static';
    }
}
