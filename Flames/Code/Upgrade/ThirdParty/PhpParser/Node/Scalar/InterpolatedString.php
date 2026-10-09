<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\InterpolatedStringPart;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
class InterpolatedString extends Scalar
{
    /**
     * Constructs an interpolated string node.
     *
     * @param (Expr|InterpolatedStringPart)[] $parts Interpolated string parts
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(public array $parts, array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    public function getSubNodeNames(): array
    {
        return ['parts'];
    }
    public function getType(): string
    {
        return 'Scalar_InterpolatedString';
    }
}
// @deprecated compatibility alias
class_alias(\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString::class, \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Encapsed::class);
