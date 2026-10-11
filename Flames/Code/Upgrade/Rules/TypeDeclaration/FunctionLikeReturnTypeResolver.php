<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class FunctionLikeReturnTypeResolver
{
    public function __construct(private StaticTypeMapper $staticTypeMapper)
    {
    }
    public function resolveFunctionLikeReturnTypeToPHPStanType(ClassMethod $classMethod): Type
    {
        $functionReturnType = $classMethod->getReturnType();
        if ($functionReturnType === null) {
            return new MixedType();
        }
        return $this->staticTypeMapper->mapPhpParserNodePHPStanType($functionReturnType);
    }
}
