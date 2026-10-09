<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\StringNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Enum\MappingClass;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
final class ColumnPropertyTypeResolver
{
    /**
     * @var array<string, Type>
     * @readonly
     */
    private array $doctrineTypeToScalarType;
    private const string DATE_TIME_INTERFACE = 'DateTimeInterface';
    /**
     * @param array<string, Type> $doctrineTypeToScalarType
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html#doctrine-mapping-types
     */
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly TypeFactory $typeFactory, private readonly AttributeFinder $attributeFinder, ?array $doctrineTypeToScalarType = null)
    {
        $doctrineTypeToScalarType ??= [
            'tinyint' => new BooleanType(),
            'boolean' => new BooleanType(),
            // integers
            'smallint' => new IntegerType(),
            'mediumint' => new IntegerType(),
            'int' => new IntegerType(),
            'integer' => new IntegerType(),
            'numeric' => new IntegerType(),
            // floats
            'float' => new FloatType(),
            'double' => new FloatType(),
            'real' => new FloatType(),
            // strings
            'decimal' => new StringType(),
            'bigint' => new StringType(),
            'tinytext' => new StringType(),
            'mediumtext' => new StringType(),
            'longtext' => new StringType(),
            'text' => new StringType(),
            'varchar' => new StringType(),
            'string' => new StringType(),
            'char' => new StringType(),
            'longblob' => new MixedType(),
            'blob' => new MixedType(),
            'mediumblob' => new MixedType(),
            'tinyblob' => new MixedType(),
            'binary' => new StringType(),
            'varbinary' => new StringType(),
            'set' => new StringType(),
            // date time objects
            'date' => new ObjectType(self::DATE_TIME_INTERFACE),
            'datetime' => new ObjectType(self::DATE_TIME_INTERFACE),
            'timestamp' => new ObjectType(self::DATE_TIME_INTERFACE),
            'time' => new ObjectType(self::DATE_TIME_INTERFACE),
            'year' => new ObjectType(self::DATE_TIME_INTERFACE),
        ];
        $this->doctrineTypeToScalarType = $doctrineTypeToScalarType;
    }
    public function resolve(Property $property, bool $isNullable): ?Type
    {
        $expr = $this->attributeFinder->findAttributeByClassArgByName($property, MappingClass::COLUMN, 'type');
        if ($expr instanceof String_) {
            return $this->createPHPStanTypeFromDoctrineStringType($expr->value, $isNullable);
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        return $this->resolveFromPhpDocInfo($phpDocInfo, $isNullable);
    }
    private function resolveFromPhpDocInfo(PhpDocInfo $phpDocInfo, bool $isNullable): ?\PHPStan\Type\Type
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass(MappingClass::COLUMN);
        if (!$doctrineAnnotationTagValueNode instanceof DoctrineAnnotationTagValueNode) {
            return null;
        }
        $typeArrayItemNode = $doctrineAnnotationTagValueNode->getValue('type');
        if (!$typeArrayItemNode instanceof ArrayItemNode) {
            return new MixedType();
        }
        $typeValue = $typeArrayItemNode->value;
        if ($typeValue instanceof StringNode) {
            $typeValue = $typeValue->value;
        }
        if (!is_string($typeValue)) {
            return null;
        }
        return $this->createPHPStanTypeFromDoctrineStringType($typeValue, $isNullable);
    }
    private function createPHPStanTypeFromDoctrineStringType(string $type, bool $isNullable): Type
    {
        $scalarType = $this->doctrineTypeToScalarType[$type] ?? null;
        if (!$scalarType instanceof Type) {
            return new MixedType();
        }
        $types = [$scalarType];
        if ($isNullable) {
            $types[] = new NullType();
        }
        return $this->typeFactory->createMixedPassedOrUnionType($types);
    }
}
