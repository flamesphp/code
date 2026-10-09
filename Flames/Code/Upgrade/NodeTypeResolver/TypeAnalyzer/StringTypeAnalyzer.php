<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\TypeAnalyzer;

use PhpParser\Node\Expr;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class StringTypeAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function isStringOrUnionStringOnlyType(Expr $expr): bool
    {
        $nodeType = $this->nodeTypeResolver->getType($expr);
        return $nodeType->isString()->yes();
    }
}
