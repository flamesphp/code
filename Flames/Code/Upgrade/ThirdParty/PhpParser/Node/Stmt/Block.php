<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
class Block extends Stmt
{
    /**
     * A block of statements.
     *
     * @param Stmt[] $stmts Statements
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $stmts, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getType(): string
    {
        return 'Stmt_Block';
    }
    public function getSubNodeNames(): array
    {
        return ['stmts'];
    }
}
