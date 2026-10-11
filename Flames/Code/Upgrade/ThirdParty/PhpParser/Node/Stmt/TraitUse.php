<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class TraitUse extends Node\Stmt
{
    /**
     * Constructs a trait use node.
     *
     * @param Node\Name[] $traits Traits
     * @param TraitUseAdaptation[] $adaptations Adaptations
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $traits, public array $adaptations = [], array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['traits', 'adaptations'];
    }
    public function getType(): string
    {
        return 'Stmt_TraitUse';
    }
}
