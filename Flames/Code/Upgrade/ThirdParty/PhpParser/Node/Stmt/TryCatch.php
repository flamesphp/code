<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class TryCatch extends Node\Stmt
{
    /**
     * Constructs a try catch node.
     *
     * @param Node\Stmt[] $stmts Statements
     * @param Catch_[] $catches Catches
     * @param null|Finally_ $finally Optional finally node
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $stmts, public array $catches, public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Finally_ $finally = null, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['stmts', 'catches', 'finally'];
    }
    public function getType(): string
    {
        return 'Stmt_TryCatch';
    }
}
