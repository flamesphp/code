<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
class PostInc extends Expr
{
    /**
     * Constructs a post increment node.
     *
     * @param Expr $var Variable
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Expr $var, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['var'];
    }
    public function getType(): string
    {
        return 'Expr_PostInc';
    }
}
