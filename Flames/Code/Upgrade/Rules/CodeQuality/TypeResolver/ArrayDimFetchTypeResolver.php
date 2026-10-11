<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\TypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class ArrayDimFetchTypeResolver
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function resolve(ArrayDimFetch $arrayDimFetch, Assign $assign): ArrayType
    {
        $keyStaticType = $this->resolveDimType($arrayDimFetch);
        $valueStaticType = $this->nodeTypeResolver->getType($assign->expr);
        return new ArrayType($keyStaticType, $valueStaticType);
    }
    private function resolveDimType(ArrayDimFetch $arrayDimFetch): Type
    {
        if ($arrayDimFetch->dim instanceof Expr) {
            return $this->nodeTypeResolver->getType($arrayDimFetch->dim);
        }
        return new MixedType();
    }
}
