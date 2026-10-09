<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Arguments\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class ChangedArgumentsDetector
{
    public function __construct(private ValueResolver $valueResolver, private StaticTypeMapper $staticTypeMapper, private TypeComparator $typeComparator)
    {
    }
    /**
     * @param mixed $value
     */
    public function isDefaultValueChanged(Param $param, $value): bool
    {
        if (!$param->default instanceof Expr) {
            return \false;
        }
        return !$this->valueResolver->isValue($param->default, $value);
    }
    public function isTypeChanged(Param $param, ?Type $newType): bool
    {
        if (!$param->type instanceof Node) {
            return \false;
        }
        if (!$newType instanceof Type) {
            return \true;
        }
        $currentParamType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($param->type);
        return !$this->typeComparator->areTypesEqual($currentParamType, $newType);
    }
}
