<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\TypeAnalyzer;

use PhpParser\Node\Expr;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class ArrayTypeAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function isArrayType(Expr $expr): bool
    {
        $nodeType = $this->nodeTypeResolver->getNativeType($expr);
        return $nodeType->isArray()->yes();
    }
}
