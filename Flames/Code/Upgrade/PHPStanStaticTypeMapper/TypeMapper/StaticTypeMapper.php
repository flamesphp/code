<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\SelfStaticType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\SimpleStaticType;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\StaticTypeMapper\StaticTypeMapperTest
 *
 * @implements TypeMapperInterface<StaticType>
 */
final readonly class StaticTypeMapper implements TypeMapperInterface
{
    public function __construct(private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [StaticType::class];
    }
    /**
     * @param StaticType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param SimpleStaticType|StaticType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): Name
    {
        if ($type instanceof SelfStaticType) {
            return new Name(ObjectReference::SELF);
        }
        if ($typeKind !== TypeKind::RETURN) {
            return new Name(ObjectReference::SELF);
        }
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::STATIC_RETURN_TYPE)) {
            return new Name(ObjectReference::SELF);
        }
        return new Name(ObjectReference::STATIC);
    }
}
