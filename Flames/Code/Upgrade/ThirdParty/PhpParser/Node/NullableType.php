<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
class NullableType extends \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType
{
    /**
     * Constructs a nullable type (wrapping another type).
     *
     * @param Identifier|Name $type Type
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public Node $type, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['type'];
    }
    public function getType(): string
    {
        return 'NullableType';
    }
}
