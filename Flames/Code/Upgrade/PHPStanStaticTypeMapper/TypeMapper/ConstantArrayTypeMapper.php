<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @implements TypeMapperInterface<ConstantArrayType>
 */
final readonly class ConstantArrayTypeMapper implements TypeMapperInterface
{
    public function __construct(private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [ConstantArrayType::class];
    }
    /**
     * @param ConstantArrayType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::SCALAR_TYPES)) {
            return null;
        }
        return new Identifier('array');
    }
}
