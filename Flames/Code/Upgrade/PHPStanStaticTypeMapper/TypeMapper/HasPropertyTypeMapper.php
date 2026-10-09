<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
/**
 * @implements TypeMapperInterface<HasPropertyType>
 */
final readonly class HasPropertyTypeMapper implements TypeMapperInterface
{
    public function __construct(private \Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\ObjectWithoutClassTypeMapper $objectWithoutClassTypeMapper)
    {
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [HasPropertyType::class];
    }
    /**
     * @param HasPropertyType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param HasPropertyType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        return $this->objectWithoutClassTypeMapper->mapToPhpParserNode($type, $typeKind);
    }
}
