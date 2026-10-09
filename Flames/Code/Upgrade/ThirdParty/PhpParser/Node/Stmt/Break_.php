<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class Break_ extends Node\Stmt
{
    /**
     * Constructs a break node.
     *
     * @param null|Node\Expr $num Number of loops to break
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public ?Node\Expr $num = null, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['num'];
    }
    public function getType(): string
    {
        return 'Stmt_Break';
    }
}
