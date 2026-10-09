<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
/**
 * @implements TypeMapperInterface<CallableType>
 */
final class CallableTypeMapper implements TypeMapperInterface
{
    /**
     * @return array<class-string<Type>>
     */
    public function getNodeClasses(): array
    {
        return [CallableType::class];
    }
    /**
     * @param CallableType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        return $type->toPhpDocNode();
    }
    /**
     * @param TypeKind::* $typeKind
     * @param CallableType|ClosureType $type
     */
    public function mapToPhpParserNode(Type $type, string $typeKind): ?Node
    {
        if ($typeKind === TypeKind::PROPERTY) {
            return null;
        }
        return new Identifier('callable');
    }
}
