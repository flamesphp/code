<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class Const_ extends NodeAbstract
{
    /** @var Identifier Name */
    public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier $name;
    /** @var Name|null Namespaced name (if using NameResolver) */
    public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name $namespacedName = null;
    /**
     * Constructs a const node for use in class const and const statements.
     *
     * @param string|Identifier $name Name
     * @param Expr $value Value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier($name) : $name;
    }
    public function getSubNodeNames(): array
    {
        return ['name', 'value'];
    }
    public function getType(): string
    {
        return 'Const';
    }
}
