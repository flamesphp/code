<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\NonExistingObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\SelfObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
/**
 * @implements TypeMapperInterface<ObjectType>
 */
final class ObjectTypeMapper implements TypeMapperInterface
{
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [ObjectType::class];
    }
    /**
     * @param ObjectType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof ArrayType && ($type->getItemType() instanceof MixedType && $type->getKeyType() instanceof MixedType)) {
                return new ArrayType(new MixedType(), new MixedType(\true));
            }
            if (!$type instanceof ObjectType) {
                return $traverse($type);
            }
            $typeClass = $type::class;
            // early native ObjectType check
            if ($typeClass === \PHPStan\Type\ObjectType::class) {
                return new ObjectType('\\' . $type->getClassName());
            }
            if ($type instanceof FullyQualifiedObjectType) {
                return new ObjectType('\\' . $type->getClassName());
            }
            if ($type instanceof GenericObjectType) {
                return $traverse(new GenericObjectType('\\' . $type->getClassName(), $type->getTypes()), $traverse);
            }
            return $traverse($type, $traverse);
        });
        return $type->toPhpDocNode();
    }
    /**
     * @param ObjectType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        if ($type instanceof SelfObjectType) {
            return new Name('self');
        }
        if ($type instanceof ShortenedObjectType || $type instanceof AliasedObjectType) {
            return new FullyQualified($type->getFullyQualifiedName());
        }
        if ($type instanceof FullyQualifiedObjectType) {
            $className = $type->getClassName();
            if (str_starts_with($className, '\\')) {
                // skip leading \
                return new FullyQualified(Strings::substring($className, 1));
            }
            return new FullyQualified($className);
        }
        if ($type instanceof NonExistingObjectType) {
            return null;
        }
        return new FullyQualified($type->getClassName());
    }
}
