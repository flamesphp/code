<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer;

use PhpParser\Node\Expr;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
final readonly class NullableTypeAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function resolveNullableObjectType(Expr $expr): ?\PHPStan\Type\ObjectType
    {
        $exprType = $this->nodeTypeResolver->getNativeType($expr);
        $baseType = TypeCombinator::removeNull($exprType);
        if (!$baseType instanceof ObjectType) {
            return null;
        }
        return $baseType;
    }
}
