<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp72\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class NativeParamToPhpDocDecorator
{
    public function __construct(private PhpDocInfoFactory $phpDocInfoFactory, private NodeNameResolver $nodeNameResolver, private StaticTypeMapper $staticTypeMapper, private PhpDocTypeChanger $phpDocTypeChanger, private ValueResolver $valueResolver)
    {
    }
    public function decorate(ClassMethod $classMethod, Param $param): void
    {
        if (!$param->type instanceof Node) {
            return;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        $paramName = $this->nodeNameResolver->getName($param);
        $mappedCurrentParamType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($param->type);
        $correctedNullableParamType = $this->correctNullableType($param, $mappedCurrentParamType);
        $this->phpDocTypeChanger->changeParamType($classMethod, $phpDocInfo, $correctedNullableParamType, $param, $paramName);
    }
    private function isParamNullable(Param $param): bool
    {
        if (!$param->default instanceof Expr) {
            return \false;
        }
        return $this->valueResolver->isNull($param->default);
    }
    /**
     * @return \PHPStan\Type\UnionType|\PHPStan\Type\Type
     */
    private function correctNullableType(Param $param, Type $paramType)
    {
        if (!$this->isParamNullable($param)) {
            return $paramType;
        }
        if (TypeCombinator::containsNull($paramType)) {
            return $paramType;
        }
        // add default null type
        return TypeCombinator::addNull($paramType);
    }
}
