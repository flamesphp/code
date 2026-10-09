<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @implements TypeMapperInterface<ClassStringType>
 */
final readonly class ClassStringTypeMapper implements TypeMapperInterface
{
    public function __construct(private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [ClassStringType::class];
    }
    /**
     * @param ClassStringType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if (!$type instanceof ObjectType) {
                return $traverse($type);
            }
            $typeClass = $type::class;
            if ($typeClass === \PHPStan\Type\ObjectType::class) {
                return new ObjectType('\\' . $type->getClassName());
            }
            return $traverse($type);
        });
        return $type->toPhpDocNode();
    }
    /**
     * @param ClassStringType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::SCALAR_TYPES)) {
            return null;
        }
        return new Identifier('string');
    }
}
