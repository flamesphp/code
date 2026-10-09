<?php

declare (strict_types=1);
namespace PhpParser\Node\Scalar;

use PhpParser\Node\Expr;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar;
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
class_alias(\PhpParser\Node\Scalar\InterpolatedString::class, \PhpParser\Node\Scalar\Encapsed::class);
