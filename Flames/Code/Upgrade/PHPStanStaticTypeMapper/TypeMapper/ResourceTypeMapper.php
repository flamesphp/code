<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
/**
 * @implements TypeMapperInterface<ResourceType>
 */
final class ResourceTypeMapper implements TypeMapperInterface
{
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [ResourceType::class];
    }
    /**
     * @param ResourceType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param ResourceType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        return null;
    }
}
