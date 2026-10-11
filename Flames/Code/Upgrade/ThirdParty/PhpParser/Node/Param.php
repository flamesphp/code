<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeAbstract;
class Param extends NodeAbstract
{
    /**
     * Constructs a parameter node.
     *
     * @param Expr\Variable|Expr\Error $var Parameter variable
     * @param null|Expr $default Default value
     * @param null|Identifier|Name|ComplexType $type Type declaration
     * @param bool $byRef Whether is passed by reference
     * @param bool $variadic Whether this is a variadic argument
     * @param array<string, mixed> $attributes Additional attributes
     * @param int $flags Optional visibility flags
     * @param list<AttributeGroup> $attrGroups PHP attribute groups
     * @param PropertyHook[] $hooks Property hooks for promoted properties
     */
    public function __construct(public \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $var, public ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $default = null, public ?Node $type = null, public bool $byRef = \false, public bool $variadic = \false, array $attributes = [], public int $flags = 0, public array $attrGroups = [], public array $hooks = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['attrGroups', 'flags', 'type', 'byRef', 'variadic', 'var', 'default', 'hooks'];
    }
    public function getType(): string
    {
        return 'Param';
    }
    /**
     * Whether this parameter uses constructor property promotion.
     */
    public function isPromoted(): bool
    {
        return $this->flags !== 0 || $this->hooks !== [];
    }
    public function isFinal(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    public function isPublic(): bool
    {
        $public = (bool) ($this->flags & Modifiers::PUBLIC);
        if ($public) {
            return \true;
        }
        if (!$this->isPromoted()) {
            return \false;
        }
        return ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
    }
    public function isProtected(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED);
    }
    public function isPrivate(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE);
    }
    public function isReadonly(): bool
    {
        return (bool) ($this->flags & Modifiers::READONLY);
    }
    /**
     * Whether the promoted property has explicit public(set) visibility.
     */
    public function isPublicSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PUBLIC_SET);
    }
    /**
     * Whether the promoted property has explicit protected(set) visibility.
     */
    public function isProtectedSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED_SET);
    }
    /**
     * Whether the promoted property has explicit private(set) visibility.
     */
    public function isPrivateSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE_SET);
    }
}
