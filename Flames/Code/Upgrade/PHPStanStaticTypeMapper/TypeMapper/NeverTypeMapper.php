<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
/**
 * @implements TypeMapperInterface<NeverType>
 */
final class NeverTypeMapper implements TypeMapperInterface
{
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [NeverType::class];
    }
    /**
     * @param NeverType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param NeverType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        return null;
    }
}
