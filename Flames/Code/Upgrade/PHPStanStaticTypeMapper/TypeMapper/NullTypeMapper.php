<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @implements TypeMapperInterface<NullType>
 */
final readonly class NullTypeMapper implements TypeMapperInterface
{
    public function __construct(private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [NullType::class];
    }
    /**
     * @param NullType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param TypeKind::* $typeKind
     * @param NullType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        // can be a standalone type, only case where null makes sense
        if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::NULL_FALSE_TRUE_STANDALONE_TYPE) && $typeKind === TypeKind::RETURN) {
            return new Identifier('null');
        }
        // if part of union, can be added even in PHP 8.0
        if ($typeKind === TypeKind::UNION && $this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::NULLABLE_TYPE)) {
            return new Identifier('null');
        }
        return null;
    }
}
